<?php

namespace Tests\Feature;

use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PDOException;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_returns_ok_when_database_available(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJson([
                'status' => 'ok',
                'checks' => ['database' => 'ok'],
            ]);
    }

    public function test_health_returns_unhealthy_when_database_connection_fails(): void
    {
        $connection = \Mockery::mock(\Illuminate\Database\Connection::class);
        $connection->shouldReceive('getPdo')->once()->andThrow(new PDOException('Simulated failure'));

        $database = \Mockery::mock(DatabaseManager::class);
        $database->shouldReceive('connection')->once()->andReturn($connection);
        $this->instance(DatabaseManager::class, $database);

        $this->getJson('/health')
            ->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'checks' => ['database' => 'failed'],
            ]);
    }
}
