<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'zip_path',
        'preview_image',
        'min_plan_level',
        'description',
        'is_active',
    ];

    protected $appends = [
        'zip_exists',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getZipExistsAttribute(): bool
    {
        $disk = config('wordpress.theme_storage_disk', 'themes');

        return Storage::disk($disk)->exists($this->zip_path);
    }
}