<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'user_id',
        'fingerprint',
        'platform',
        'ip_address',
        'user_agent',
        'risk_score',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'risk_score' => 'integer',
            'last_seen_at' => 'datetime',
        ];
    }
}
