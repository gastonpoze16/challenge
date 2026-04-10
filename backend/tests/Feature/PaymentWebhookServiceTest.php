<?php

namespace Tests\Feature;

use App\Events\PaymentsListRefreshBroadcast;
use App\Models\EventLog;
use App\Models\Payment;
use App\Models\PaymentEventType;
use App\Services\PaymentWebhookService;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class PaymentWebhookServiceTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    private PaymentWebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
        $this->service = app(PaymentWebhookService::class);
    }

    public function test_creates_event_log_and_payment(): void
    {
        Event::fake();
        $result = ($this->service)($this->validWebhookPayload());

        $this->assertTrue($result['event_logged']);
        $this->assertTrue($result['payment_updated']);
        $this->assertDatabaseHas('payments', ['payment_id' => 'pay_001']);
        $this->assertDatabaseHas('event_logs', ['event_id' => 'evt_001']);
    }

    public function test_duplicate_does_not_update_payment(): void
    {
        Event::fake();
        ($this->service)($this->validWebhookPayload());
        $result = ($this->service)($this->validWebhookPayload());

        $this->assertFalse($result['payment_updated']);
        $this->assertCount(2, EventLog::where('event_id', 'evt_001')->get());
    }

    public function test_upserts_on_new_event(): void
    {
        Event::fake();
        ($this->service)($this->validWebhookPayload());
        ($this->service)($this->validWebhookPayload([
            'event_id' => 'evt_002', 'event' => 'payment.completed',
        ]));

        $payment = Payment::where('payment_id', 'pay_001')->first();
        $this->assertEquals('evt_002', $payment->last_event_id);
        $this->assertEquals('payment.completed', $payment->event);
    }

    public function test_broadcasts_on_new_event_only(): void
    {
        Event::fake();
        ($this->service)($this->validWebhookPayload());
        Event::assertDispatched(PaymentsListRefreshBroadcast::class);

        Event::fake();
        ($this->service)($this->validWebhookPayload());
        Event::assertNotDispatched(PaymentsListRefreshBroadcast::class);
    }

    public function test_throws_on_unknown_event_code(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ($this->service)($this->validWebhookPayload(['event' => 'payment.nonexistent']));
    }

    public function test_stores_correct_event_type_fk(): void
    {
        Event::fake();
        ($this->service)($this->validWebhookPayload());

        $typeId = PaymentEventType::where('code', 'payment.created')->value('id');
        $this->assertDatabaseHas('payments', ['payment_id' => 'pay_001', 'payment_event_type_id' => $typeId]);
        $this->assertDatabaseHas('event_logs', ['event_id' => 'evt_001', 'payment_event_type_id' => $typeId]);
    }
}
