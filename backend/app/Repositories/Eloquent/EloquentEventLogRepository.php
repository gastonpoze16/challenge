<?php

namespace App\Repositories\Eloquent;

use App\Models\EventLog;
use App\Repositories\Contracts\EventLogRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentEventLogRepository implements EventLogRepositoryInterface
{
    public function store(array $data): EventLog
    {
        return EventLog::query()->create($data);
    }

    public function findByPaymentId(string $paymentId): Collection
    {
        return EventLog::query()
            ->where('payment_id', $paymentId)
            ->with('eventType')
            ->orderBy('timestamp')
            ->get();
    }

    public function existsForPaymentAndEventId(string $paymentId, string $eventId): bool
    {
        return EventLog::query()
            ->where('payment_id', $paymentId)
            ->where('event_id', $eventId)
            ->exists();
    }
}
