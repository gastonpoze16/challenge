<?php

use App\Http\Controllers\Api\Admin\AdminPaymentRefundController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Payments\PaymentController;
use App\Http\Controllers\Api\Payments\PaymentEventTypeController;
use App\Http\Controllers\Api\Payments\PaymentExportController;
use App\Http\Controllers\Api\Payments\PaymentMetricsController;
use App\Http\Controllers\Api\Webhooks\PaymentWebhookController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

/*
| Readiness: BD + app. Para balanceadores / Datadog HTTP check / orquestación Docker.
| Liveness ligero del framework: GET /up (bootstrap/app.php).
*/
Route::get('/health', HealthController::class);

Route::post('/webhooks/payment', [PaymentWebhookController::class, 'store'])
    ->middleware('throttle:webhook');

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/payments/metrics', PaymentMetricsController::class);
    Route::get('/payments/export', PaymentExportController::class);
    Route::get('/payments/{id}/events', [PaymentController::class, 'events']);
    Route::get('/payments', [PaymentController::class, 'index']);
    Route::get('/payment-event-types', PaymentEventTypeController::class);
    Route::post('/admin/payments/{paymentId}/refund', AdminPaymentRefundController::class);
});
