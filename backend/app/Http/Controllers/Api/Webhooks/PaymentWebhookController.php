<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentWebhookRequest;
use App\Jobs\ProcessPaymentWebhookJob;
use Illuminate\Http\JsonResponse;

class PaymentWebhookController extends Controller
{
    public function store(StorePaymentWebhookRequest $request): JsonResponse
    {
        ProcessPaymentWebhookJob::dispatch($request->validated());

        return response()->json([
            'message' => 'Webhook accepted for processing.',
        ], 202);
    }
}
