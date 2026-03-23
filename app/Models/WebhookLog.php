<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'event_type',
        'event_object',
        'signature',
        'payload_hash',
        'is_valid',
        'is_processed',
        'processed_at',
        'raw_payload',
        'headers',
        'processing_error',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'is_processed' => 'boolean',
        'processed_at' => 'datetime',
        'headers' => 'array',
    ];
}