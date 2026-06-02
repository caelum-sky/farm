<?php

namespace App\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceItem extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const CATEGORY_EQUIPMENT = 'equipment';
    public const CATEGORY_GOODS = 'goods';

    public const TRANSACTION_SALE = 'sale';
    public const TRANSACTION_RENT = 'rent';

    public const LIFECYCLE_REVIEW = 'review';
    public const LIFECYCLE_ACTIVE = 'active';
    public const LIFECYCLE_HIDDEN = 'hidden';
    public const LIFECYCLE_REJECTED = 'rejected';

    protected $fillable = [
        'owner_id',
        'title',
        'category',
        'transaction_type',
        'price',
        'rent_rate',
        'unit',
        'quantity',
        'condition',
        'location',
        'harvest_date',
        'description',
        'image_url',
        'is_featured',
        'is_available',
        'report_count',
        'moderation_status',
        'lifecycle_status',
        'flagged_reason',
        'published_at',
        'latitude',
        'longitude',
        'geo_hash',
        'reserved_quantity',
        'min_order_quantity',
        'max_order_quantity',
        'deposit_amount',
        'cancellation_policy',
        'cancellation_policy_id',
        'freshness_expires_at',
        'listing_score',
        'last_inventory_sync_at',
        'legal_hold_until',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'rent_rate' => 'decimal:2',
            'quantity' => 'decimal:2',
            'reserved_quantity' => 'decimal:2',
            'min_order_quantity' => 'decimal:2',
            'max_order_quantity' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'harvest_date' => 'date',
            'published_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'freshness_expires_at' => 'datetime',
            'is_featured' => 'boolean',
            'is_available' => 'boolean',
            'report_count' => 'integer',
            'listing_score' => 'integer',
            'last_inventory_sync_at' => 'datetime',
            'legal_hold_until' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function cancellationPolicy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function availabilityBlocks(): HasMany
    {
        return $this->hasMany(ListingAvailabilityBlock::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function bookingLines(): HasMany
    {
        return $this->hasMany(BookingLine::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(EquipmentMaintenanceLog::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function fraudSignals(): MorphMany
    {
        return $this->morphMany(FraudSignal::class, 'signalable');
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->available()
            ->where('moderation_status', 'approved')
            ->where('lifecycle_status', self::LIFECYCLE_ACTIVE);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_available
            && $this->moderation_status === 'approved'
            && $this->lifecycle_status === self::LIFECYCLE_ACTIVE;
    }

    public function isRental(): bool
    {
        return $this->transaction_type === self::TRANSACTION_RENT;
    }

    public function availableQuantity(): float
    {
        $reservedByRequests = (float) $this->inquiries()
            ->whereIn('status', Inquiry::RESERVING_STATUSES)
            ->sum('quantity');
        $reserved = max((float) $this->reserved_quantity, $reservedByRequests);

        return max(0, (float) $this->quantity - $reserved);
    }

    public function hasRentalConflict(string $startDate, string $endDate, float $requestedQuantity): bool
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $blocked = $this->availabilityBlocks()
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start)
            ->exists();

        if ($blocked) {
            return true;
        }

        $reservedByRequests = (float) $this->inquiries()
            ->whereIn('status', Inquiry::RESERVING_STATUSES)
            ->whereDate('start_date', '<=', $end)
            ->whereDate('end_date', '>=', $start)
            ->sum('quantity');

        $reservedByBookings = (float) $this->bookings()
            ->whereIn('status', Booking::RESERVING_STATUSES)
            ->where('starts_at', '<=', $end)
            ->where('ends_at', '>=', $start)
            ->sum('quantity');

        $reserved = max($reservedByRequests, $reservedByBookings);

        return $reserved + $requestedQuantity > (float) $this->quantity;
    }

    public function priceLabel(): string
    {
        if ($this->isRental()) {
            return '$'.number_format((float) $this->rent_rate, 2).' / '.$this->unit;
        }

        return '$'.number_format((float) $this->price, 2).' / '.$this->unit;
    }

    public function typeLabel(): string
    {
        return $this->category === self::CATEGORY_EQUIPMENT
            ? ($this->isRental() ? 'Equipment rental' : 'Equipment sale')
            : 'Harvested goods';
    }
}
