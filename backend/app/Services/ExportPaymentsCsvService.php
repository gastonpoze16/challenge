<?php

namespace App\Services;

use App\Repositories\Contracts\PaymentRepositoryInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportPaymentsCsvService
{
    public function __construct(private readonly PaymentRepositoryInterface $paymentRepository) {}

    public function __invoke(array $filters): StreamedResponse
    {
        $payments = $this->paymentRepository->listAll($filters);

        return new StreamedResponse(function () use ($payments): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['payment_id', 'status', 'amount', 'currency', 'user_id', 'updated_at']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->payment_id,
                    $payment->event,
                    $payment->amount,
                    $payment->currency,
                    $payment->user_id,
                    $payment->updated_at?->toIso8601String(),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="payments.csv"',
        ]);
    }
}
