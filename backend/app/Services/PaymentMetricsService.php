<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentEventType;
use Illuminate\Support\Facades\DB;

class PaymentMetricsService
{
    /**
     * @return array{total: int, by_status: list<array{event: string, label: string, count: int}>, by_day: list<array{date: string, count: int}>, by_currency: list<array{currency: string, count: int}>}
     */
    public function __invoke(): array
    {
        $baseQuery = Payment::query();

        $total = (clone $baseQuery)->count();

        $typesById = PaymentEventType::query()
            ->orderBy('sort_order')
            ->get()
            ->keyBy('id');

        $byStatusRaw = (clone $baseQuery)
            ->select('payment_event_type_id', DB::raw('COUNT(*) as count'))
            ->groupBy('payment_event_type_id')
            ->pluck('count', 'payment_event_type_id');

        $byStatus = [];
        foreach ($typesById as $id => $type) {
            $byStatus[] = [
                'event' => $type->code,
                'label' => $type->label,
                'count' => (int) ($byStatusRaw[$id] ?? 0),
            ];
        }

        $byDay = (clone $baseQuery)
            ->select(DB::raw('DATE(updated_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
            ])
            ->all();

        $byCurrency = (clone $baseQuery)
            ->select('currency', DB::raw('COUNT(*) as count'))
            ->groupBy('currency')
            ->orderBy('count', 'desc')
            ->get()
            ->map(fn ($row) => [
                'currency' => $row->currency,
                'count' => (int) $row->count,
            ])
            ->all();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'by_day' => $byDay,
            'by_currency' => $byCurrency,
        ];
    }
}
