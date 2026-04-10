<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'payment_id',
        'payment_event_type_id',
        'amount',
        'currency',
        'user_id',
        'last_event_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    protected $hidden = [
        'payment_event_type_id',
        'eventType',
    ];

    protected $appends = [
        'event',
    ];

    public function eventType(): BelongsTo
    {
        return $this->belongsTo(PaymentEventType::class, 'payment_event_type_id');
    }

    /**
     * Código de negocio expuesto en JSON (p. ej. payment.completed); el dato persistido es la FK.
     */
    public function getEventAttribute(): ?string
    {
        return $this->eventType?->code;
    }
}
