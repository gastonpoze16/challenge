<?php

namespace App\Repositories\Contracts;

use App\Models\EventLog;
use Illuminate\Support\Collection;

interface EventLogRepositoryInterface
{
    public function store(array $data): EventLog;

    public function findByPaymentId(string $paymentId): Collection;

    /**
     * True if this payment already received this event_id at least once (applied to state before this POST).
     */
    public function existsForPaymentAndEventId(string $paymentId, string $eventId): bool;
}
