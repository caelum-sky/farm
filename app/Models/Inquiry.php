<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inquiry extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_EXPIRED = 'expired';

    public const RESERVING_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
    ];

    protected $fillable = [
        'type',
        'marketplace_item_id',
        'user_id',
        'message',
        'quantity',
        'start_date',
        'end_date',
        'contact_phone',
        'contact_email',
        'status',
        'subtotal_amount',
        'tax_amount',
        'platform_fee_amount',
        'escrow_amount',
        'payment_status',
        'escrow_status',
        'dispute_status',
        'seller_accepted_at',
        'buyer_confirmed_at',
        'fulfilled_at',
        'cancelled_at',
        'expires_at',
        'return_due_at',
        'version',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'platform_fee_amount' => 'decimal:2',
            'escrow_amount' => 'decimal:2',
            'seller_accepted_at' => 'datetime',
            'buyer_confirmed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'expires_at' => 'datetime',
            'return_due_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function marketplaceItem(): BelongsTo
    {
        return $this->belongsTo(MarketplaceItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'source_inquiry_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function escrowTransactions(): HasMany
    {
        return $this->hasMany(EscrowTransaction::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function messages(): MorphMany
    {
        return $this->morphMany(Message::class, 'messageable');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
