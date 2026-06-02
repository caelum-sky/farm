<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Booking extends Model
{
    use SoftDeletes;

    public const STATUS_REQUESTED = 'requested';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DISPUTED = 'disputed';

    public const RESERVING_STATUSES = [
        self::STATUS_REQUESTED,
        self::STATUS_CONFIRMED,
        self::STATUS_ACTIVE,
    ];

    protected $fillable = [
        'order_id',
        'inquiry_id',
        'marketplace_item_id',
        'renter_id',
        'owner_id',
        'operator_user_id',
        'pickup_inspection_id',
        'return_inspection_id',
        'status',
        'quantity',
        'starts_at',
        'ends_at',
        'deposit_amount',
        'late_fee_amount',
        'damage_claim_amount',
        'late_fee_daily_amount',
        'picked_up_at',
        'returned_at',
        'legal_hold_until',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'deposit_amount' => 'decimal:2',
            'late_fee_amount' => 'decimal:2',
            'damage_claim_amount' => 'decimal:2',
            'late_fee_daily_amount' => 'decimal:2',
            'picked_up_at' => 'datetime',
            'returned_at' => 'datetime',
            'legal_hold_until' => 'datetime',
        ];
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class, 'marketplace_item_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function renter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function pickupInspection(): BelongsTo
    {
        return $this->belongsTo(InspectionReport::class, 'pickup_inspection_id');
    }

    public function returnInspection(): BelongsTo
    {
        return $this->belongsTo(InspectionReport::class, 'return_inspection_id');
    }

    public function lines(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BookingLine::class);
    }
}
