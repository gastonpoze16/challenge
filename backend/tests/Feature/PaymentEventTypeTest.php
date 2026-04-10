<?php

namespace Tests\Feature;

use App\Models\PaymentEventType;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class PaymentEventTypeTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
    }

    public function test_codes_returns_all_event_codes(): void
    {
        $codes = PaymentEventType::codes();

        $this->assertContains('payment.created', $codes);
        $this->assertContains('payment.completed', $codes);
        $this->assertContains('payment.failed', $codes);
        $this->assertContains('payment.refunded', $codes);
        $this->assertCount(4, $codes);
    }

    public function test_codes_are_cached_and_invalidated_on_save(): void
    {
        $this->assertCount(4, PaymentEventType::codes());

        PaymentEventType::create([
            'code' => 'payment.pending',
            'label' => 'Pending',
            'sort_order' => 5,
            'is_refunded' => false,
        ]);

        $codes = PaymentEventType::codes();
        $this->assertCount(5, $codes);
        $this->assertContains('payment.pending', $codes);
    }

    public function test_api_endpoint_requires_auth(): void
    {
        $this->getJson('/payment-event-types')->assertStatus(401);
    }

    public function test_api_endpoint_returns_event_types(): void
    {
        [, , $headers] = $this->createAuthenticatedUser();

        $this->getJson('/payment-event-types', $headers)
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['code', 'label', 'sort_order', 'is_refunded']]])
            ->assertJsonCount(4, 'data');
    }
}
