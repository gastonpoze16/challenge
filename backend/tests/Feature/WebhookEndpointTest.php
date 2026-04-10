<?php

namespace Tests\Feature;

use App\Jobs\ProcessPaymentWebhookJob;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class WebhookEndpointTest extends TestCase
{
    use RefreshDatabase, PaymentTestHelper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentEventTypeSeeder::class);
    }

    public function test_returns_202_and_dispatches_job(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/payment', $this->validWebhookPayload())
            ->assertStatus(202)
            ->assertJson(['message' => 'Webhook accepted for processing.']);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, fn ($job) =>
            $job->payload['event_id'] === 'evt_001');
    }

    public function test_validates_required_fields(): void
    {
        $this->postJson('/webhooks/payment', [])
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors']);
    }

    public function test_rejects_invalid_event_code(): void
    {
        $this->postJson('/webhooks/payment', $this->validWebhookPayload(['event' => 'bad.event']))
            ->assertStatus(422)
            ->assertJsonPath('errors.event.0', fn ($v) => str_contains($v, 'must be one of'));
    }

    public function test_rejects_invalid_currency(): void
    {
        $this->postJson('/webhooks/payment', $this->validWebhookPayload(['currency' => 'US']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('currency');
    }

    public function test_rejects_negative_amount(): void
    {
        $this->postJson('/webhooks/payment', $this->validWebhookPayload(['amount' => -10]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('amount');
    }

    public function test_normalizes_currency_to_uppercase(): void
    {
        Queue::fake();

        $this->postJson('/webhooks/payment', $this->validWebhookPayload(['currency' => 'usd']))
            ->assertStatus(202);

        Queue::assertPushed(ProcessPaymentWebhookJob::class, fn ($job) =>
            $job->payload['currency'] === 'USD');
    }
}
