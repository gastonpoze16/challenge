<?php

namespace Tests\Helpers;

use App\Models\Payment;
use App\Models\PaymentEventType;
use App\Models\User;

trait PaymentTestHelper
{
    private function validWebhookPayload(array $overrides = []): array
    {
        return array_merge([
            'event_id' => 'evt_001',
            'payment_id' => 'pay_001',
            'event' => 'payment.created',
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => 1,
            'timestamp' => '2026-04-10T12:00:00Z',
        ], $overrides);
    }

    private function createPayment(array $overrides = []): Payment
    {
        $defaults = [
            'payment_id' => 'pay_test',
            'event' => 'payment.completed',
            'amount' => 100.00,
            'currency' => 'USD',
            'user_id' => $this->user->id ?? 1,
            'last_event_id' => 'evt_test',
        ];

        $data = array_merge($defaults, $overrides);
        $eventCode = $data['event'];
        unset($data['event']);

        $data['payment_event_type_id'] = PaymentEventType::where('code', $eventCode)->value('id');

        return Payment::create($data);
    }

    private function createAuthenticatedUser(): array
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        return [$user, $token, ['Authorization' => "Bearer $token"]];
    }
}
