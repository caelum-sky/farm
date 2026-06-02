<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListingAvailabilityBlock extends Model
{
    protected $fillable = [
        'marketplace_item_id',
        'type',
        'starts_at',
        'ends_at',
        'reason',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function marketplaceItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
