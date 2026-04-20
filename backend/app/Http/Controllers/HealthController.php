<?php

namespace App\Http\Controllers;

use App\Services\HealthReadinessService;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __construct(
        private readonly HealthReadinessService $healthReadiness,
    ) {}

    public function __invoke(): JsonResponse
    {
        $payload = $this->healthReadiness->check();

        $status = $payload['status'] === 'ok' ? 200 : 503;

        return response()->json($payload, $status);
    }
}
