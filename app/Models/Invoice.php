<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_VOID = 'void';

    protected $fillable = [
        'subscription_id',
        'user_id',
        'site_id',
        'invoice_number',
        'provider_invoice_id',
        'provider_charge_id',
        'status',
        'currency',
        'amount',
        'billing_period_start',
        'billing_period_end',
        'due_at',
        'paid_at',
        'failed_at',
        'failure_reason',
        'meta',
    ];

    protected $casts = [
        'billing_period_start' => 'datetime',
        'billing_period_end' => 'datetime',
        'due_at' => 'datetime',
        'paid_at' => 'datetime',
        'failed_at' => 'datetime',
        'meta' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}