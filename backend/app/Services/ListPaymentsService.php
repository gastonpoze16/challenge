<?php

namespace App\Services;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPaymentsService
{
    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {}

    /**
     * @param  array{user_id?: int|null, event?: string|null, date_from?: string|null, date_to?: string|null, currency?: string|null}  $filters
     */
    public function __invoke(int $perPage = 15, int $page = 1, array $filters = []): LengthAwarePaginator
    {
        return $this->paymentRepository->list($perPage, $page, $filters);
    }
}
