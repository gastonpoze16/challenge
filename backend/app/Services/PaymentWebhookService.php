<?php

namespace App\Services;

use App\Events\PaymentsListRefreshBroadcast;
use App\Models\PaymentEventType;
use App\Repositories\Contracts\EventLogRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService
{
    public function __construct(
        private readonly EventLogRepositoryInterface $eventLogRepository,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {}

    public function __invoke(array $payload): array
    {
        Log::info('Incoming payment webhook received', [
            'event_id' => $payload['event_id'],
            'payment_id' => $payload['payment_id'],
            'user_id' => $payload['user_id'] ?? null,
            'event' => $payload['event'],
        ]);

        return DB::transaction(function () use ($payload): array {
            $paymentEventTypeId = PaymentEventType::query()
                ->where('code', $payload['event'])
                ->value('id');

            if ($paymentEventTypeId === null) {
                throw new \InvalidArgumentException('Unknown payment event type code: '.$payload['event']);
            }

            // Idempotency: if this payment_id + event_id already exists, Payment already reflects it.
            $eventAlreadyApplied = $this->eventLogRepository->existsForPaymentAndEventId(
                $payload['payment_id'],
                $payload['event_id']
            );

            $this->eventLogRepository->store([
                'event_id' => $payload['event_id'],
                'payment_id' => $payload['payment_id'],
                'payment_event_type_id' => $paymentEventTypeId,
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'user_id' => $payload['user_id'] ?? null,
                'timestamp' => $payload['timestamp'],
                'received_at' => now(),
            ]);

            if ($eventAlreadyApplied) {
                Log::warning('Duplicate payment webhook event detected', [
                    'event_id' => $payload['event_id'],
                    'payment_id' => $payload['payment_id'],
                    'user_id' => $payload['user_id'] ?? null,
                ]);

                return [
                    'event_logged' => true,
                    'payment_updated' => false,
                    'message' => 'Duplicate event_id for this payment. EventLog stored; Payment state unchanged.',
                ];
            }

            $payment = $this->paymentRepository->upsert([
                'payment_id' => $payload['payment_id'],
                'payment_event_type_id' => $paymentEventTypeId,
                'amount' => $payload['amount'],
                'currency' => $payload['currency'],
                'user_id' => $payload['user_id'] ?? null,
                'last_event_id' => $payload['event_id'],
            ]);

            // Señal vacía para que el front refresque la lista (sin datos sensibles por WS).
            // Se emite en cada upsert exitoso (pago nuevo o actualizado), no solo en altas.
            try {
                broadcast(new PaymentsListRefreshBroadcast);
            } catch (\Throwable $e) {
                Log::warning('Payments list refresh broadcast failed', [
                    'payment_id' => $payload['payment_id'],
                    'exception' => $e->getMessage(),
                ]);
            }

            Log::info('Payment state updated from webhook', [
                'event_id' => $payload['event_id'],
                'payment_id' => $payload['payment_id'],
                'user_id' => $payload['user_id'] ?? null,
                'last_event_id' => $payment->last_event_id,
            ]);

            return [
                'event_logged' => true,
                'payment_updated' => true,
                'payment_id' => $payment->payment_id,
                'last_event_id' => $payment->last_event_id,
            ];
        });
    }
}
