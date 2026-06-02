<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory;
    use Notifiable;
    use SoftDeletes;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_FARMER = 'farmer';
    public const ROLE_SELLER = 'seller';
    public const ROLE_BUYER = 'buyer';
    public const ROLE_COOPERATIVE = 'cooperative';
    public const ROLE_DRIVER = 'driver';
    public const ROLE_INSPECTOR = 'inspector';

    public const SELLER_ROLES = [
        self::ROLE_ADMIN,
        self::ROLE_FARMER,
        self::ROLE_SELLER,
        self::ROLE_COOPERATIVE,
    ];

    protected $fillable = [
        'organization_id',
        'name',
        'username',
        'email',
        'password',
        'farm_name',
        'profile_picture',
        'bio',
        'phone',
        'phone_verified_at',
        'phone_verification_code_hash',
        'phone_verification_expires_at',
        'phone_verification_attempts',
        'location',
        'address',
        'gender',
        'birthdate',
        'role',
        'status',
        'kyc_status',
        'risk_score',
        'theme',
        'dashboard_range',
        'profile_notes',
        'notification_email',
        'profile_visibility',
        'share_location',
        'last_login_at',
        'api_token_hash',
        'api_token_created_at',
        'password_changed_at',
        'force_password_change',
        'two_factor_confirmed_at',
        'legal_hold_until',
        'payout_provider_account_id',
        'tax_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token_hash',
        'phone_verification_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'phone_verified_at' => 'datetime',
            'phone_verification_expires_at' => 'datetime',
            'phone_verification_attempts' => 'integer',
            'notification_email' => 'boolean',
            'share_location' => 'boolean',
            'birthdate' => 'date',
            'risk_score' => 'integer',
            'last_login_at' => 'datetime',
            'api_token_created_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'force_password_change' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'legal_hold_until' => 'datetime',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function marketplaceItems(): HasMany
    {
        return $this->hasMany(MarketplaceItem::class, 'owner_id');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function buyerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function sellerOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function renterBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'renter_id');
    }

    public function ownerBookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'owner_id');
    }

    public function savedSearches(): HasMany
    {
        return $this->hasMany(SavedSearch::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function fraudSignals(): HasMany
    {
        return $this->hasMany(FraudSignal::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $this->roles
            ->flatMap(fn (Role $role): array => $role->permissions ?? [])
            ->contains($permission);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canCreateListings(): bool
    {
        return $this->isActive() && in_array($this->role, self::SELLER_ROLES, true);
    }

    public function canBypassModeration(): bool
    {
        return $this->isAdmin();
    }
}
