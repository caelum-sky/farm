<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CancellationPolicy extends Model
{
    protected $fillable = [
        'name',
        'label',
        'free_cancellation_hours',
        'seller_no_show_penalty_rate',
        'buyer_late_cancel_penalty_rate',
        'terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'free_cancellation_hours' => 'integer',
            'seller_no_show_penalty_rate' => 'decimal:2',
            'buyer_late_cancel_penalty_rate' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function listings(): HasMany
    {
        return $this->hasMany(MarketplaceItem::class);
    }
}
