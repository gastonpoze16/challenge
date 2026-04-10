<?php

namespace App\Jobs;

use App\Services\PaymentWebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessPaymentWebhookJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public array $backoff = [5, 15, 60, 300];

    public function __construct(
        public readonly array $payload
    ) {}

    public function handle(PaymentWebhookService $service): void
    {
        Log::info('ProcessPaymentWebhookJob starting', $this->queueContext());

        ($service)($this->payload);

        Log::info('ProcessPaymentWebhookJob completed', $this->queueContext());
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessPaymentWebhookJob failed permanently', [
            ...$this->queueContext(),
            'exception' => $exception->getMessage(),
        ]);
    }

    private function queueContext(): array
    {
        return [
            'event_id' => $this->payload['event_id'] ?? null,
            'payment_id' => $this->payload['payment_id'] ?? null,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries,
            'queue' => $this->queue ?? 'default',
            'job_id' => $this->job?->getJobId(),
        ];
    }
}
