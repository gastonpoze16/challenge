<?php

use App\Http\Controllers\Api\Admin\AdminPaymentRefundController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Payments\PaymentController;
use App\Http\Controllers\Api\Payments\PaymentEventTypeController;
use App\Http\Controllers\Api\Webhooks\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/payment', [PaymentWebhookController::class, 'store']);

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/payments/{id}/events', [PaymentController::class, 'events']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payment-event-types', PaymentEventTypeController::class);
    Route::post('/admin/payments/{paymentId}/refund', AdminPaymentRefundController::class);
});
