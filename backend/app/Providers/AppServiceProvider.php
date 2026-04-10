<?php

namespace App\Providers;

use App\Repositories\Contracts\EventLogRepositoryInterface;
use App\Repositories\Contracts\PaymentRepositoryInterface;
use App\Repositories\Eloquent\EloquentEventLogRepository;
use App\Repositories\Eloquent\EloquentPaymentRepository;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(EventLogRepositoryInterface::class, EloquentEventLogRepository::class);
        $this->app->bind(PaymentRepositoryInterface::class, EloquentPaymentRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('webhook', function (Request $request) {
            return Limit::perMinute(10000)->by($request->ip());
        });
    }
}
