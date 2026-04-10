<?php

namespace Database\Seeders;

use App\Models\PaymentEventType;
use Illuminate\Database\Seeder;

class PaymentEventTypeSeeder extends Seeder
{
    /**
     * Tipos alineados con el webhook y el dashboard (única fuente de verdad para labels y validación).
     */
    public function run(): void
    {
        $rows = [
            ['code' => 'payment.created', 'label' => 'Created', 'sort_order' => 10, 'is_refunded' => false],
            ['code' => 'payment.completed', 'label' => 'Completed', 'sort_order' => 20, 'is_refunded' => false],
            ['code' => 'payment.failed', 'label' => 'Failed', 'sort_order' => 30, 'is_refunded' => false],
            ['code' => 'payment.refunded', 'label' => 'Refunded', 'sort_order' => 40, 'is_refunded' => true],
        ];

        foreach ($rows as $row) {
            PaymentEventType::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'label' => $row['label'],
                    'sort_order' => $row['sort_order'],
                    'is_refunded' => $row['is_refunded'],
                ]
            );
        }

        PaymentEventType::clearCodesCache();
    }
}
