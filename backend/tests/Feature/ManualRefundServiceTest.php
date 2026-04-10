<?php

namespace Tests\Feature;

use App\Services\ManualRefundPaymentService;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class ManualRefundServiceTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    private ManualRefundPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
        $this->service = app(ManualRefundPaymentService::class);
    }

    public function test_refund_creates_refunded_payment_state(): void
    {
        Event::fake();
        $payment = $this->createPayment();

        $result = ($this->service)($payment);

        $this->assertTrue($result['payment_updated']);
        $payment->refresh()->load('eventType');
        $this->assertEquals('payment.refunded', $payment->event);
    }

    public function test_refund_already_refunded_throws_validation_error(): void
    {
        Event::fake();
        $this->expectException(ValidationException::class);
        ($this->service)($this->createPayment(['event' => 'payment.refunded']));
    }

    public function test_refund_creates_event_log(): void
    {
        Event::fake();
        ($this->service)($this->createPayment());

        $this->assertDatabaseHas('event_logs', ['payment_id' => 'pay_test']);
    }
}
