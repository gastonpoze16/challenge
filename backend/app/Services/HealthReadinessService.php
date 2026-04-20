<?php

namespace App\Services;

use Illuminate\Database\DatabaseManager;
use Throwable;

class HealthReadinessService
{
    public function __construct(
        private readonly DatabaseManager $database,
    ) {}

    /**
     * @return array{status: string, checks: array<string, string>}
     */
    public function check(): array
    {
        try {
            $this->database->connection()->getPdo();

            return [
                'status' => 'ok',
                'checks' => ['database' => 'ok'],
            ];
        } catch (Throwable) {
            return [
                'status' => 'unhealthy',
                'checks' => ['database' => 'failed'],
            ];
        }
    }
}
