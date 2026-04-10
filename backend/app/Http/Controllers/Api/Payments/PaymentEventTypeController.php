<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\PaymentEventType;
use Illuminate\Http\JsonResponse;

class PaymentEventTypeController extends Controller
{
    /**
     * Catálogo para filtros, etiquetas y reglas de UI (p. ej. reembolso).
     */
    public function __invoke(): JsonResponse
    {
        $data = PaymentEventType::query()
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get(['code', 'label', 'sort_order', 'is_refunded']);

        return response()->json([
            'data' => $data,
        ]);
    }
}
