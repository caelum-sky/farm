<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FraudSignal extends Model
{
    protected $fillable = [
        'signalable_type',
        'signalable_id',
        'user_id',
        'type',
        'score',
        'severity',
        'reason',
        'metadata',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'metadata' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function signalable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
