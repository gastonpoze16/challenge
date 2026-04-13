<?php

namespace App\Http\Controllers\Api\Payments;

use App\Http\Controllers\Controller;
use App\Models\PaymentEventType;
use App\Services\ExportPaymentsCsvService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentExportController extends Controller
{
    public function __construct(private readonly ExportPaymentsCsvService $exportService) {}

    public function __invoke(Request $request): StreamedResponse
    {
        $eventRule = Rule::in(PaymentEventType::codes());

        $validated = $request->validate([
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

        $filters = [
            'owner_user_id' => (int) $request->user()->id,
            'event' => $validated['event'] ?? $validated['status'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'currency' => isset($validated['currency']) ? strtoupper($validated['currency']) : null,
        ];

        return ($this->exportService)($filters);
    }
}
