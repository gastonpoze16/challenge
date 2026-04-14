<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Models\PaymentEventType;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EloquentPaymentRepository implements PaymentRepositoryInterface
{
    public function upsert(array $data): Payment
    {
        $payment = Payment::query()->firstOrNew([
            'payment_id' => $data['payment_id'],
        ]);

        $payment->fill($data);
        $payment->save();

        return $payment;
    }

    public function findByPaymentId(string $paymentId): ?Payment
    {
        return Payment::query()
            ->where('payment_id', $paymentId)
            ->first();
    }

    public function list(int $perPage = 15, int $page = 1, array $filters = []): LengthAwarePaginator
    {
        return $this->buildFilteredQuery($filters)
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function listAll(array $filters = []): Collection
    {
        return $this->buildFilteredQuery($filters)->get();
    }

    private function buildFilteredQuery(array $filters): Builder
    {
        $query = Payment::query();

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['event'])) {
            $typeId = PaymentEventType::where('code', $filters['event'])->value('id');
            if ($typeId) {
                $query->where('payment_event_type_id', $typeId);
            } else {
                $query->whereRaw('0 = 1');
            }
        }

        if (! empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('updated_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('updated_at', '<=', $filters['date_to']);
        }

        return $query
            ->with('eventType')
            ->orderByDesc('updated_at');
    }
}
