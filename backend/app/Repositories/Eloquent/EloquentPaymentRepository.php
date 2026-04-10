<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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
        $query = Payment::query()->where('user_id', $filters['owner_user_id']);

        if (! empty($filters['event'])) {
            $query->whereHas('eventType', function ($q) use ($filters): void {
                $q->where('code', $filters['event']);
            });
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
            ->orderByDesc('updated_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
