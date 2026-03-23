<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAST_DUE = 'past_due';
    public const STATUS_GRACE_PERIOD = 'grace_period';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'plan_id',
        'site_id',
        'provider',
        'provider_plan_id',
        'provider_subscription_id',
        'provider_customer_reference',
        'status',
        'currency',
        'amount',
        'billing_cycle',
        'starts_at',
        'next_billing_at',
        'last_paid_at',
        'grace_ends_at',
        'suspended_at',
        'cancelled_at',
        'expired_at',
        'notes',
        'meta',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'next_billing_at' => 'datetime',
        'last_paid_at' => 'datetime',
        'grace_ends_at' => 'datetime',
        'suspended_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentAttempts(): HasMany
    {
        return $this->hasMany(PaymentAttempt::class);
    }
}