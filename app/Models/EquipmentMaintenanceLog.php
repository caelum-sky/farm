<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentMaintenanceLog extends Model
{
    protected $fillable = [
        'marketplace_item_id',
        'performed_by',
        'status',
        'maintenance_type',
        'due_date',
        'completed_date',
        'cost_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'completed_date' => 'date',
            'cost_amount' => 'decimal:2',
        ];
    }

    public function marketplaceItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class);
    }
}
