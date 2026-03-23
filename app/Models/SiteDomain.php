<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteDomain extends Model
{
    protected $fillable = [
        'site_id',
        'domain',
        'is_primary',
        'is_verified',
        'verification_status',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}