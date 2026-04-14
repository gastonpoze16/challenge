<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Helpers\PaymentTestHelper;
use Tests\TestCase;

class PaymentExportTest extends TestCase
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

    public function test_export_requires_auth(): void
    {
        $this->get('/payments/export')->assertUnauthorized();
    }

    public function test_export_returns_csv(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p2', 'user_id' => $this->user->id, 'event' => 'payment.failed']);

        $response = $this->get('/payments/export', $this->headers);

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="payments.csv"');

        $csv = $response->streamedContent();
        $this->assertStringContains('payment_id,status,amount,currency,user_id,updated_at', $csv);
        $this->assertStringContains('p1', $csv);
        $this->assertStringContains('p2', $csv);
    }

    public function test_export_respects_event_filter(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'event' => 'payment.completed', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p2', 'event' => 'payment.failed', 'user_id' => $this->user->id]);

        $response = $this->get('/payments/export?event=payment.completed', $this->headers);

        $csv = $response->streamedContent();
        $this->assertStringContains('p1', $csv);
        $this->assertStringNotContains('p2', $csv);
    }

    public function test_export_respects_currency_filter(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'currency' => 'USD', 'user_id' => $this->user->id]);
        $this->createPayment(['payment_id' => 'p2', 'currency' => 'EUR', 'user_id' => $this->user->id]);

        $response = $this->get('/payments/export?currency=USD', $this->headers);

        $csv = $response->streamedContent();
        $this->assertStringContains('p1', $csv);
        $this->assertStringNotContains('p2', $csv);
    }

    public function test_export_without_user_filter_includes_all_payments(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'user_id' => $this->user->id]);

        $otherUser = \App\Models\User::factory()->create();
        $this->createPayment(['payment_id' => 'p2', 'user_id' => $otherUser->id]);

        $csv = $this->get('/payments/export', $this->headers)->streamedContent();
        $this->assertStringContains('p1', $csv);
        $this->assertStringContains('p2', $csv);
    }

    public function test_export_respects_user_id_filter(): void
    {
        $this->createPayment(['payment_id' => 'p1', 'user_id' => $this->user->id]);

        $otherUser = \App\Models\User::factory()->create();
        $this->createPayment(['payment_id' => 'p2', 'user_id' => $otherUser->id]);

        $csv = $this->get('/payments/export?user_id='.$otherUser->id, $this->headers)->streamedContent();
        $this->assertStringNotContains('p1', $csv);
        $this->assertStringContains('p2', $csv);
    }

    private function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(str_contains($haystack, $needle), "Failed asserting that CSV contains '$needle'.");
    }

    private function assertStringNotContains(string $needle, string $haystack): void
    {
        $this->assertFalse(str_contains($haystack, $needle), "Failed asserting that CSV does not contain '$needle'.");
    }
}
