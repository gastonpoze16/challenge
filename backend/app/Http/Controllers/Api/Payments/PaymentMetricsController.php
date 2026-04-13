<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Services\PaymentMetricsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMetricsController extends Controller
{
    public function __construct(private readonly PaymentMetricsService $metricsService) {}

    public function __invoke(Request $request): JsonResponse
    {
        $metrics = ($this->metricsService)((int) $request->user()->id);

        return response()->json($metrics);
    }
}
