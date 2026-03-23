<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProvisioningLog extends Model
{
    protected $fillable = [
        'site_id',
        'action',
        'status',
        'message',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}