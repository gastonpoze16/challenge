<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventLog extends Model
{
    protected $fillable = [
        'event_id',
        'payment_id',
        'payment_event_type_id',
        'amount',
        'currency',
        'user_id',
        'timestamp',
        'received_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'timestamp' => 'datetime',
        'received_at' => 'datetime',
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

    public function getEventAttribute(): ?string
    {
        return $this->eventType?->code;
    }
}
