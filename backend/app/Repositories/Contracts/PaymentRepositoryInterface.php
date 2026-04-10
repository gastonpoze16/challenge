<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface PaymentRepositoryInterface
{
    public function upsert(array $data): Payment;

    public function findByPaymentId(string $paymentId): ?Payment;

    /**
     * @param  array{owner_user_id: int, event?: string|null, date_from?: string|null, date_to?: string|null, currency?: string|null}  $filters
     */
    public function list(int $perPage = 15, int $page = 1, array $filters = []): LengthAwarePaginator;
}
