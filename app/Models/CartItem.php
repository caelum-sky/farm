<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    protected $fillable = [
        'cart_id',
        'marketplace_item_id',
        'quantity',
        'start_date',
        'end_date',
        'quoted_unit_amount',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'quoted_unit_amount' => 'decimal:2',
        ];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }
}
