<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'invoice_id',
        'provider_event_type',
        'provider_charge_id',
        'provider_reference',
        'status',
        'amount',
        'currency',
        'attempted_at',
        'succeeded_at',
        'failed_at',
        'failure_reason',
        'payload',
    ];

    protected $casts = [
        'attempted_at' => 'datetime',
        'succeeded_at' => 'datetime',
        'failed_at' => 'datetime',
        'payload' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}