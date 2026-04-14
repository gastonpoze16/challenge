<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Services\PaymentMetricsService;
use Illuminate\Http\JsonResponse;

class PaymentMetricsController extends Controller
{
    public function __construct(private readonly PaymentMetricsService $metricsService) {}

    public function __invoke(): JsonResponse
    {
        $metrics = ($this->metricsService)();

        return response()->json($metrics);
    }
}
