<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class PaymentMetricsEndpointTest extends TestCase
{
    use PaymentTestHelper;
    use RefreshDatabase;

    private $user;

    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\PaymentEventTypeSeeder::class);
        [$this->user, , $this->headers] = $this->createAuthenticatedUser();
    }

    public function test_metrics_requires_auth(): void
    {
        $this->getJson('/payments/metrics')->assertUnauthorized();
    }

    public function test_metrics_returns_total_count(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p2', 'user_id' => $this->user->id]);

        $response = $this->getJson('/payments/metrics', $this->headers);

        $response->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_metrics_returns_by_status(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'event' => 'payment.completed', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p2', 'event' => 'payment.completed', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p3', 'event' => 'payment.failed', 'user_id' => $this->user->id]);

        $response = $this->getJson('/payments/metrics', $this->headers);

        $response->assertOk()
            ->assertJsonStructure(['total', 'by_status', 'by_day', 'by_currency']);

        $byStatus = collect($response->json('by_status'));

        $this->assertEquals(2, $byStatus->firstWhere('event', 'payment.completed')['count']);
        $this->assertEquals(1, $byStatus->firstWhere('event', 'payment.failed')['count']);
        $this->assertEquals(0, $byStatus->firstWhere('event', 'payment.created')['count']);
    }

    public function test_metrics_returns_by_day(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p2', 'user_id' => $this->user->id]);

        $response = $this->getJson('/payments/metrics', $this->headers);

        $response->assertOk()
            ->assertJsonStructure(['by_day' => [['date', 'count']]]);

        $byDay = $response->json('by_day');
        $totalFromDays = array_sum(array_column($byDay, 'count'));
        $this->assertEquals(2, $totalFromDays);
    }

    public function test_metrics_returns_by_currency(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'currency' => 'USD', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p2', 'currency' => 'EUR', 'user_id' => $this->user->id]);

        $response = $this->getJson('/payments/metrics', $this->headers);

        $response->assertOk()
            ->assertJsonStructure(['by_currency' => [['currency', 'count']]]);

        $currencies = array_column($response->json('by_currency'), 'currency');
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
    }

    public function test_metrics_includes_all_payments(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'user_id' => $this->user->id]);

        $otherUser = \App\Models\User::factory()->create();
        $this->createPayment(['payment_id' => 'p2', 'user_id' => $otherUser->id]);

        $response = $this->getJson('/payments/metrics', $this->headers);

        $response->assertOk()
            ->assertJsonPath('total', 2);
    }

    public function test_metrics_empty_for_new_user(): void
    {
        $response = $this->getJson('/payments/metrics', $this->headers);

        $response->assertOk()
            ->assertJsonPath('total', 0);

        $byStatus = collect($response->json('by_status'));
        $this->assertTrue($byStatus->every(fn ($s) => $s['count'] === 0));
    }
}
