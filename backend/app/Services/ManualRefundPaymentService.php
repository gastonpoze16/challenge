<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentEventType;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManualRefundPaymentService
{
    public function __construct(
        private readonly PaymentWebhookService $paymentWebhookService
    ) {}

    /**
     * Construye un payload `payment.refunded` y lo procesa con la misma lógica que POST /webhooks/payment.
     */
    public function __invoke(Payment $payment): array
    {
        $payment->loadMissing('eventType');

        if ($payment->eventType?->is_refunded) {
            throw ValidationException::withMessages([
                'payment_id' => ['This payment has already been refunded.'],
            ]);
        }

        $refundEventCode = PaymentEventType::query()
            ->where('is_refunded', true)
            ->value('code');

        if ($refundEventCode === null || $refundEventCode === '') {
            abort(500, 'No payment event type is configured with is_refunded=true. Run database seeders.');
        }

        $payload = [
            'event_id' => 'refund-'.Str::uuid()->toString(),
            'payment_id' => $payment->payment_id,
            'event' => $refundEventCode,
            'amount' => $payment->amount,
            'currency' => strtoupper((string) $payment->currency),
            'user_id' => $payment->user_id,
            'timestamp' => now()->toIso8601String(),
        ];

        return ($this->paymentWebhookService)($payload);
    }
}
