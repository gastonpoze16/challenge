<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\ManualRefundPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPaymentRefundController extends Controller
{
    public function __construct(
        private readonly ManualRefundPaymentService $manualRefundPaymentService
    ) {}

    /**
     * Dispara internamente el mismo flujo que el webhook con evento `payment.refunded`.
     *
     * @param  string  $paymentId  payment_id de negocio (no PK de la tabla)
     */
    public function __invoke(Request $request, string $paymentId): JsonResponse
    {
        $payment = Payment::query()
            ->where('payment_id', $paymentId)
            ->where('user_id', $request->user()->id)
            ->first();

        if (! $payment) {
            abort(404);
        }

        $result = ($this->manualRefundPaymentService)($payment);

        return response()->json([
            'message' => 'Refund webhook processed.',
            'data' => $result,
        ]);
    }
}
