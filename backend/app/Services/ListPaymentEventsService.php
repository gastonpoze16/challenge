<?php

namespace App\Services;

use App\Repositories\Contracts\EventLogRepositoryInterface;
use Illuminate\Support\Collection;

class ListPaymentEventsService
{
    public function __construct(
        private readonly EventLogRepositoryInterface $eventLogRepository
    ) {}

    public function __invoke(string $paymentId): Collection
    {
        return $this->eventLogRepository->findByPaymentId($paymentId);
    }
}
