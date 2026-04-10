<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PaymentWebhookService;
use Database\Seeders\PaymentEventTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class PaymentControllerTest extends TestCase
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

    public function test_list_payments_requires_auth(): void
    {
        $this->getJson('/payments')->assertStatus(401);
    }

    public function test_list_payments_returns_paginated_data(): void
    {
        $this->createPayment(['payment_id' => 'pay_1']);
        $this->createPayment(['payment_id' => 'pay_2']);

        $this->getJson('/payments?limit=10&page=1', $this->headers)
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to']])
            ->assertJsonPath('meta.total', 2);
    }

    public function test_list_payments_filters_by_event(): void
    {
        $this->createPayment(['payment_id' => 'pay_created', 'event' => 'payment.created']);
        $this->createPayment(['payment_id' => 'pay_completed']);

        $this->getJson('/payments?event=payment.completed', $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.payment_id', 'pay_completed');
    }

    public function test_list_payments_filters_by_currency(): void
    {
        $this->createPayment(['payment_id' => 'pay_usd']);
        $this->createPayment(['payment_id' => 'pay_eur', 'currency' => 'EUR']);

        $this->getJson('/payments?currency=EUR', $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.payment_id', 'pay_eur');
    }

    public function test_list_payments_only_shows_own_payments(): void
    {
        $this->createPayment(['payment_id' => 'pay_mine']);

        $otherUser = User::factory()->create();
        $this->createPayment(['payment_id' => 'pay_other', 'user_id' => $otherUser->id]);

        $this->getJson('/payments', $this->headers)
            ->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.payment_id', 'pay_mine');
    }

    public function test_events_returns_event_history(): void
    {
        Event::fake();
        $service = app(PaymentWebhookService::class);

        $base = ['payment_id' => 'pay_hist', 'amount' => 100, 'currency' => 'USD', 'user_id' => $this->user->id];
        ($service)(array_merge($base, ['event_id' => 'evt_1', 'event' => 'payment.created', 'timestamp' => '2026-04-10T10:00:00Z']));
        ($service)(array_merge($base, ['event_id' => 'evt_2', 'event' => 'payment.completed', 'timestamp' => '2026-04-10T11:00:00Z']));

        $this->getJson('/payments/pay_hist/events', $this->headers)
            ->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_events_returns_404_for_other_users_payment(): void
    {
        $otherUser = User::factory()->create();
        $this->createPayment(['payment_id' => 'pay_not_mine', 'user_id' => $otherUser->id]);

        $this->getJson('/payments/pay_not_mine/events', $this->headers)
            ->assertStatus(404);
    }

    public function test_date_range_validation(): void
    {
        $this->getJson('/payments?date_from=2026-04-10&date_to=2026-04-01', $this->headers)
            ->assertStatus(422)
            ->assertJsonValidationErrors('date_to');
    }
}
