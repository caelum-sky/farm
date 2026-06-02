<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KycVerification extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_reference',
        'status',
        'document_type',
        'checks',
        'review_notes',
        'submitted_at',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'checks' => 'array',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }
}
