<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class PaymentEventType extends Model
{
    protected $fillable = [
        'code',
        'label',
        'sort_order',
        'is_refunded',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_refunded' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::cacheKey()));
        static::deleted(fn () => Cache::forget(self::cacheKey()));
    }

    public static function cacheKey(): string
    {
        return 'payment_event_type_codes';
    }

    /**
     * @return list<string>
     */
    public static function codes(): array
    {
        return Cache::rememberForever(self::cacheKey(), function () {
            return static::query()
                ->orderBy('sort_order')
                ->orderBy('code')
                ->pluck('code')
                ->all();
        });
    }

    public static function clearCodesCache(): void
    {
        Cache::forget(self::cacheKey());
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_event_type_id');
    }

    public function eventLogs(): HasMany
    {
        return $this->hasMany(EventLog::class, 'payment_event_type_id');
    }
}
