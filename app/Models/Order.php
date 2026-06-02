<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_ESCROW_HELD = 'escrow_held';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DISPUTED = 'disputed';
    public const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'order_number',
        'invoice_number',
        'buyer_id',
        'seller_id',
        'organization_id',
        'source_inquiry_id',
        'type',
        'status',
        'subtotal_amount',
        'tax_amount',
        'platform_fee_amount',
        'deposit_amount',
        'total_amount',
        'currency',
        'invoice_issued_at',
        'accepted_at',
        'confirmed_at',
        'fulfilled_at',
        'proof_of_delivery_at',
        'cancelled_at',
        'refund_deadline_at',
        'cancellation_reason',
        'version',
        'legal_hold_until',
    ];

    protected function casts(): array
    {
        return [
            'subtotal_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'platform_fee_amount' => 'decimal:2',
            'deposit_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'invoice_issued_at' => 'datetime',
            'accepted_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'fulfilled_at' => 'datetime',
            'proof_of_delivery_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'refund_deadline_at' => 'datetime',
            'version' => 'integer',
            'legal_hold_until' => 'datetime',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sourceInquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class, 'source_inquiry_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
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
}
