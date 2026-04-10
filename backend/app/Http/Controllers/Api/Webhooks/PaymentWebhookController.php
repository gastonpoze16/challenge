<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentWebhookRequest;
use App\Services\PaymentWebhookService;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function __construct(
        private readonly PaymentWebhookService $paymentWebhookService
    ) {}

    public function store(StorePaymentWebhookRequest $request): JsonResponse
    {
        $result = ($this->paymentWebhookService)($request->validated());

        return response()->json([
            'message' => 'Webhook processed successfully.',
            'data' => $result,
        ]);
    }
}
