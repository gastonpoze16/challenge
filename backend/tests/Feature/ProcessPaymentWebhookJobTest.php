<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaymentWebhookJob;
use App\Models\EventLog;
use App\Models\Payment;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class ProcessPaymentWebhookJobTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
    }

    public function test_persists_payment_and_event_log(): void
    {
        $job = new ProcessPaymentWebhookJob($this->validWebhookPayload());
        app()->call([$job, 'handle']);

        $this->assertDatabaseHas('payments', ['payment_id' => 'pay_001']);
        $this->assertDatabaseHas('event_logs', ['event_id' => 'evt_001']);
    }

    public function test_handles_idempotency(): void
    {
        $payload = $this->validWebhookPayload();

        (new ProcessPaymentWebhookJob($payload))->handle(app(\App\Services\PaymentWebhookService::class));
        (new ProcessPaymentWebhookJob($payload))->handle(app(\App\Services\PaymentWebhookService::class));

        $this->assertCount(2, EventLog::where('event_id', 'evt_001')->get());
        $this->assertCount(1, Payment::where('payment_id', 'pay_001')->get());
    }

    public function test_has_correct_retry_config(): void
    {
        $job = new ProcessPaymentWebhookJob($this->validWebhookPayload());

        $this->assertEquals(5, $job->tries);
        $this->assertEquals([5, 15, 60, 300], $job->backoff);
    }

    public function test_failed_method_logs_error(): void
    {
        Log::shouldReceive('error')
            ->withArgs(fn ($msg) => str_contains($msg, 'failed permanently'))
            ->once();

        $job = new ProcessPaymentWebhookJob($this->validWebhookPayload());
        $job->failed(new \RuntimeException('DB connection lost'));
    }
}
