<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletLedgerEntry extends Model
{
    protected $fillable = [
        'wallet_id',
        'ledgerable_type',
        'ledgerable_id',
        'direction',
        'type',
        'amount',
        'balance_after',
        'status',
        'idempotency_key',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function ledgerable(): MorphTo
    {
        return $this->morphTo();
    }
}
