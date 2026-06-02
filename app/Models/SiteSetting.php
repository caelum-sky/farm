<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function defaults(): array
    {
        return [
            'homepage_headline' => 'Sell harvests, rent equipment, and keep farms moving.',
            'homepage_copy' => 'FarmBridge connects growers, buyers, and equipment owners with a focused marketplace for tractors, pumps, harvesters, seedlings, vegetables, grains, and more.',
            'site_announcement' => 'Admin-managed marketplace for farm goods, rentals, users, orders, and posts.',
            'marketplace_status' => 'open',
            'maintenance_mode' => '0',
            'orders_enabled' => '1',
            'rentals_enabled' => '1',
            'global_tax_rate' => '0',
            'platform_fee_rate' => '3',
            'listing_review_required' => '1',
            'max_active_inquiries_per_user' => '25',
            'support_email' => 'support@farmbridge.test',
            'flagged_keywords' => 'unsafe, scam, prohibited, pesticide',
        ];
    }

    public static function allSettings(): array
    {
        return array_replace(
            self::defaults(),
            self::query()->pluck('value', 'key')->all(),
        );
    }

    public static function publicSettings(): array
    {
        return self::allSettings();
    }

    public static function putMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            self::updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
