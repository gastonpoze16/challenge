<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\PaymentEventType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BenchmarkDashboard extends Command
{
    protected $signature = 'app:benchmark
        {--payments=500 : Number of payments to seed}
        {--requests=50 : Number of requests per endpoint}
        {--base-url=http://127.0.0.1:8000 : Base URL of the running server}';

    protected $description = 'Benchmark dashboard endpoint latency under load';

    public function handle(): int
    {
        $paymentCount = (int) $this->option('payments');
        $requestCount = (int) $this->option('requests');
        $baseUrl = $this->option('base-url');

        $this->info("=== Dashboard Benchmark ===\n");

        $this->info("1) Seeding {$paymentCount} payments...");
        [$user, $token] = $this->seedData($paymentCount);
        $this->info("   Done. User: {$user->email}");

        $headers = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];

        $endpoints = [
            'GET /payments' => "{$baseUrl}/payments?limit=15&page=1",
            'GET /payments (filtered)' => "{$baseUrl}/payments?limit=15&page=1&event=payment.completed&currency=USD",
            'GET /payments/metrics' => "{$baseUrl}/payments/metrics",
            'GET /payments/export' => "{$baseUrl}/payments/export",
            'GET /payments/export (filtered)' => "{$baseUrl}/payments/export?event=payment.completed",
        ];

        $this->info("\n2) GET endpoints ({$requestCount} requests each)...\n");

        $results = [];
        foreach ($endpoints as $label => $url) {
            $timings = $this->benchmarkGet($url, $headers, $requestCount);
            $results[$label] = $timings;
            $this->printResult($label, $timings);
        }

        $this->info("\n3) POST /webhooks/payment ({$requestCount} requests)...\n");

        $postTimings = $this->benchmarkWebhookPost($baseUrl, $user->id, $requestCount);
        $results['POST /webhooks/payment'] = $postTimings;
        $this->printResult('POST /webhooks/payment', $postTimings);

        $this->info("\n4) Round-trip: POST webhook → payment visible in GET ({$requestCount} requests)...\n");
        $this->info('   (requires queue:work running in a separate terminal)');

        $roundTripTimings = $this->benchmarkRoundTrip($baseUrl, $headers, $user->id, $requestCount);
        $results['Round-trip POST→GET'] = $roundTripTimings;
        $this->printResult('Round-trip POST→GET', $roundTripTimings);

        $this->info("\n5) Summary");
        $this->table(
            ['Endpoint', 'Avg (ms)', 'Min (ms)', 'Max (ms)', 'P95 (ms)', 'Reqs'],
            collect($results)->map(fn ($t, $label) => [
                $label,
                number_format($t['avg'], 1),
                number_format($t['min'], 1),
                number_format($t['max'], 1),
                number_format($t['p95'], 1),
                $t['count'],
            ])->values()->all()
        );

        $this->info("\nDataset: {$paymentCount} payments, {$requestCount} requests/endpoint.");
        $this->info('All times in milliseconds.');

        return self::SUCCESS;
    }

    private function seedData(int $count): array
    {
        $user = User::firstOrCreate(
            ['email' => 'bench@example.com'],
            ['name' => 'Benchmark User', 'password' => bcrypt('password')]
        );

        $token = $user->createToken('benchmark')->plainTextToken;

        $typeIds = PaymentEventType::pluck('id')->all();
        $currencies = ['USD', 'EUR', 'GBP', 'ARS', 'BRL'];

        $existing = Payment::where('user_id', $user->id)->count();
        $toCreate = max(0, $count - $existing);

        if ($toCreate > 0) {
            $rows = [];
            $now = now();
            for ($i = 0; $i < $toCreate; $i++) {
                $rows[] = [
                    'payment_id' => 'bench_'.Str::random(12),
                    'payment_event_type_id' => $typeIds[array_rand($typeIds)],
                    'amount' => rand(100, 99999) / 100,
                    'currency' => $currencies[array_rand($currencies)],
                    'user_id' => $user->id,
                    'last_event_id' => 'evt_'.Str::random(12),
                    'created_at' => $now->copy()->subDays(rand(0, 30)),
                    'updated_at' => $now->copy()->subDays(rand(0, 30)),
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                Payment::insert($chunk);
            }
        }

        return [$user, $token];
    }

    private function benchmarkGet(string $url, array $headers, int $count): array
    {
        $timings = [];

        for ($i = 0; $i < $count; $i++) {
            $start = microtime(true);
            $response = Http::withHeaders($headers)->timeout(30)->get($url);
            $elapsed = (microtime(true) - $start) * 1000;

            if (! $response->successful()) {
                $this->warn("   Request failed: HTTP {$response->status()} for {$url}");

                continue;
            }

            $timings[] = $elapsed;
        }

        return $this->calcStats($timings);
    }

    private function benchmarkWebhookPost(string $baseUrl, int $userId, int $count): array
    {
        $timings = [];
        $codes = PaymentEventType::pluck('code')->all();

        for ($i = 0; $i < $count; $i++) {
            $payload = [
                'event_id' => 'bench_evt_'.Str::random(12),
                'payment_id' => 'bench_post_'.Str::random(8),
                'event' => $codes[array_rand($codes)],
                'amount' => rand(100, 99999) / 100,
                'currency' => 'USD',
                'user_id' => $userId,
                'timestamp' => now()->toIso8601String(),
            ];

            $start = microtime(true);
            $response = Http::timeout(30)->post("{$baseUrl}/webhooks/payment", $payload);
            $elapsed = (microtime(true) - $start) * 1000;

            if ($response->status() !== 202) {
                $this->warn("   POST failed: HTTP {$response->status()}");

                continue;
            }

            $timings[] = $elapsed;
        }

        return $this->calcStats($timings);
    }

    private function benchmarkRoundTrip(string $baseUrl, array $headers, int $userId, int $count): array
    {
        $timings = [];
        $maxWaitMs = 5000;

        for ($i = 0; $i < $count; $i++) {
            $paymentId = 'bench_rt_'.Str::random(10);
            $payload = [
                'event_id' => 'bench_evt_'.Str::random(12),
                'payment_id' => $paymentId,
                'event' => 'payment.created',
                'amount' => 50.00,
                'currency' => 'USD',
                'user_id' => $userId,
                'timestamp' => now()->toIso8601String(),
            ];

            $start = microtime(true);
            $postResponse = Http::timeout(30)->post("{$baseUrl}/webhooks/payment", $payload);

            if ($postResponse->status() !== 202) {
                $this->warn("   POST failed: HTTP {$postResponse->status()}");

                continue;
            }

            $found = false;
            while (((microtime(true) - $start) * 1000) < $maxWaitMs) {
                usleep(20_000);
                $getResponse = Http::withHeaders($headers)->timeout(5)
                    ->get("{$baseUrl}/payments?limit=1&event=payment.created");

                if ($getResponse->successful()) {
                    $data = $getResponse->json('data', []);
                    foreach ($data as $row) {
                        if (($row['payment_id'] ?? '') === $paymentId) {
                            $found = true;

                            break 2;
                        }
                    }
                }
            }

            $elapsed = (microtime(true) - $start) * 1000;

            if (! $found) {
                $this->warn("   Round-trip timeout for {$paymentId} ({$elapsed}ms) — is queue:work running?");

                continue;
            }

            $timings[] = $elapsed;
        }

        return $this->calcStats($timings);
    }

    private function calcStats(array $timings): array
    {
        if (empty($timings)) {
            return ['avg' => 0, 'min' => 0, 'max' => 0, 'p95' => 0, 'count' => 0];
        }

        sort($timings);
        $p95Index = (int) ceil(count($timings) * 0.95) - 1;

        return [
            'avg' => array_sum($timings) / count($timings),
            'min' => $timings[0],
            'max' => end($timings),
            'p95' => $timings[$p95Index],
            'count' => count($timings),
        ];
    }

    private function printResult(string $label, array $timings): void
    {
        $this->line(sprintf(
            '   %-35s  avg=%6.1fms  min=%6.1fms  max=%6.1fms  p95=%6.1fms',
            $label,
            $timings['avg'],
            $timings['min'],
            $timings['max'],
            $timings['p95']
        ));
    }
}
