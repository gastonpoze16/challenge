<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentEventType;
use App\Services\ListPaymentEventsService;
use App\Services\ListPaymentsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(
        private readonly ListPaymentsService $listPaymentsService,
        private readonly ListPaymentEventsService $listPaymentEventsService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $eventRule = Rule::in(PaymentEventType::codes());

        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'event' => ['nullable', 'string', $eventRule],
            'status' => ['nullable', 'string', $eventRule],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'currency' => ['nullable', 'string', 'size:3', 'regex:/^[A-Za-z]{3}$/'],
        ]);

        if (! empty($validated['date_from']) && ! empty($validated['date_to'])
            && $validated['date_from'] > $validated['date_to']) {
            throw ValidationException::withMessages([
                'date_to' => ['The date to must be on or after date from.'],
            ]);
        }

        $perPage = (int) ($validated['limit'] ?? 15);
        $page = (int) ($validated['page'] ?? 1);

        $eventFilter = $validated['event'] ?? $validated['status'] ?? null;

        $filters = [
            'owner_user_id' => (int) $request->user()->id,
            'event' => $eventFilter,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'currency' => isset($validated['currency']) ? strtoupper($validated['currency']) : null,
        ];

        $payments = ($this->listPaymentsService)($perPage, $page, $filters);

        return response()->json([
            'data' => $payments->items(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
                'last_page' => $payments->lastPage(),
                'from' => $payments->firstItem(),
                'to' => $payments->lastItem(),
            ],
        ]);
    }

    /**
     * @param  string  $id  Business payment_id (not DB primary key)
     */
    public function events(Request $request, string $id): JsonResponse
    {
        $owned = Payment::query()
            ->where('payment_id', $id)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $owned) {
            abort(404);
        }

        $events = ($this->listPaymentEventsService)($id);

        return response()->json([
            'data' => $events,
        ]);
    }
}
