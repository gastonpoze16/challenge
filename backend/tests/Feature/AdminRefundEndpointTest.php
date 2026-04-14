<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class AdminRefundEndpointTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    private User $user;
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
        [$this->user, , $this->headers] = $this->createAuthenticatedUser();
    }

    public function test_refund_endpoint_requires_auth(): void
    {
        $this->postJson('/admin/payments/pay_test/refund')
            ->assertStatus(401);
    }

    public function test_refund_returns_404_for_nonexistent_payment(): void
    {
        $this->postJson('/admin/payments/nonexistent/refund', [], $this->headers)
            ->assertStatus(404);
    }

    public function test_refund_succeeds_for_payment_with_different_transaction_user_id(): void
    {
        $otherUser = User::factory()->create();
        $this->createPayment(['payment_id' => 'pay_other', 'user_id' => $otherUser->id]);

        $this->postJson('/admin/payments/pay_other/refund', [], $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('message', 'Refund webhook processed.');
    }

    public function test_refund_succeeds_for_completed_payment(): void
    {
        $this->createPayment();

        $this->postJson('/admin/payments/pay_test/refund', [], $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('message', 'Refund webhook processed.');

        $payment = Payment::where('payment_id', 'pay_test')->first();
        $this->assertEquals('payment.refunded', $payment->event);
    }

    public function test_refund_fails_for_already_refunded_payment(): void
    {
        $this->createPayment(['event' => 'payment.refunded']);

        $this->postJson('/admin/payments/pay_test/refund', [], $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors('payment_id');
    }
}
