<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'label',
        'price',
        'currency',
        'allows_custom_domain',
        'max_themes',
        'resource_profile',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'allows_custom_domain' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function getLevelAttribute(): int
    {
        return match ($this->name) {
            'bronze' => 1,
            'silver' => 2,
            'gold' => 3,
            default => 1,
        };
    }
}