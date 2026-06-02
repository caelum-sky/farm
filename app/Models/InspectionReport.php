<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionReport extends Model
{
    protected $fillable = [
        'inquiry_id',
        'marketplace_item_id',
        'inspector_id',
        'type',
        'status',
        'checklist',
        'photos',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'checklist' => 'array',
            'photos' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function marketplaceItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class);
    }
}
