<?php

namespace Database\Seeders;

use App\Models\EquipmentMaintenanceLog;
use App\Models\CancellationPolicy;
use App\Models\FraudSignal;
use App\Models\Inquiry;
use App\Models\KycVerification;
use App\Models\ListingMedia;
use App\Models\MarketplaceItem;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SavedSearch;
use App\Models\SupportTicket;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletLedgerEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::firstOrCreate(
            ['email' => 'demo@farmbridge.test'],
            [
                'name' => 'Maria Santos',
                'username' => 'maria-farm',
                'farm_name' => 'Santos Family Farm',
                'password' => Hash::make('password'),
                'phone' => '+63 912 000 1188',
                'location' => 'Nueva Ecija',
                'role' => 'farmer',
            ],
        );

        $buyer = User::firstOrCreate(
            ['email' => 'buyer@farmbridge.test'],
            [
                'name' => 'Juan Reyes',
                'username' => 'juan-buyer',
                'farm_name' => 'Reyes Grocers',
                'password' => Hash::make('password'),
                'phone' => '+63 915 441 2219',
                'location' => 'Laguna',
                'role' => 'buyer',
                'dashboard_range' => '3',
            ],
        );

        $cooperative = User::firstOrCreate(
            ['email' => 'coop@farmbridge.test'],
            [
                'name' => 'Amina Dizon',
                'username' => 'amina-coop',
                'farm_name' => 'North Valley Cooperative',
                'password' => Hash::make('password'),
                'phone' => '+63 918 887 3131',
                'location' => 'Pangasinan',
                'role' => 'cooperative',
                'dashboard_range' => '7',
            ],
        );

        $this->seedAccessControl([$owner, $buyer, $cooperative]);

        $organization = Organization::firstOrCreate(
            ['name' => 'North Valley Cooperative'],
            [
                'type' => 'cooperative',
                'status' => 'verified',
                'owner_id' => $cooperative->id,
                'registration_number' => 'COOP-2026-0001',
                'contact_email' => $cooperative->email,
                'contact_phone' => $cooperative->phone,
                'region' => 'Pangasinan',
                'verification_notes' => 'Seeded verified cooperative for pooled inventory workflows.',
                'verified_at' => now()->subDays(12),
            ],
        );

        $cooperative->update(['organization_id' => $organization->id, 'kyc_status' => 'verified']);

        OrganizationMember::firstOrCreate(
            ['organization_id' => $organization->id, 'user_id' => $cooperative->id],
            ['role' => 'owner', 'status' => 'active', 'joined_at' => now()->subDays(12)],
        );

        $this->seedWallet($owner, 420, 880);
        $this->seedWallet($buyer, 250, 0);
        $this->seedWallet($cooperative, 1250, 460);

        KycVerification::firstOrCreate(
            ['user_id' => $owner->id, 'provider_reference' => 'KYC-MARIA-001'],
            [
                'provider' => 'manual',
                'status' => 'verified',
                'document_type' => 'farm_registration',
                'checks' => ['identity' => 'passed', 'farm' => 'passed'],
                'submitted_at' => now()->subDays(20),
                'verified_at' => now()->subDays(18),
            ],
        );

        SavedSearch::firstOrCreate(
            ['user_id' => $buyer->id, 'name' => 'Nearby harvest supply'],
            ['filters' => ['category' => 'goods', 'region' => 'Laguna', 'sort' => 'distance'], 'notify' => true],
        );

        $standardPolicy = CancellationPolicy::firstOrCreate(
            ['name' => 'standard'],
            [
                'label' => 'Standard marketplace cancellation',
                'free_cancellation_hours' => 24,
                'seller_no_show_penalty_rate' => 15,
                'buyer_late_cancel_penalty_rate' => 10,
                'terms' => 'Free cancellation until 24 hours before pickup or delivery. Late cancellation may retain service fees.',
            ],
        );

        $items = [
            [
                'title' => 'Rice Combine Harvester',
                'category' => 'equipment',
                'transaction_type' => 'rent',
                'rent_rate' => 240,
                'unit' => 'day',
                'quantity' => 1,
                'condition' => 'Field ready',
                'location' => 'Central Luzon',
                'latitude' => 15.4828,
                'longitude' => 120.7120,
                'description' => 'Fuel-efficient harvester for rice fields with operator coordination available.',
                'image_url' => '/assets/equipment-tractor.png',
                'deposit_amount' => 120,
                'is_featured' => true,
                'moderation_status' => 'approved',
                'cancellation_policy_id' => $standardPolicy->id,
            ],
            [
                'title' => 'Fresh Tomato Crates',
                'category' => 'goods',
                'transaction_type' => 'sale',
                'price' => 18,
                'unit' => 'crate',
                'quantity' => 80,
                'condition' => 'Harvested today',
                'location' => 'Laguna',
                'latitude' => 14.1709,
                'longitude' => 121.2431,
                'harvest_date' => now()->subDay(),
                'freshness_expires_at' => now()->addDays(4),
                'description' => 'Firm tomatoes sorted for restaurants, groceries, and community buyers.',
                'image_url' => '/assets/produce-crates.png',
                'is_featured' => true,
                'moderation_status' => 'approved',
                'cancellation_policy_id' => $standardPolicy->id,
            ],
            [
                'title' => 'Compact Irrigation Pump',
                'category' => 'equipment',
                'transaction_type' => 'sale',
                'price' => 520,
                'unit' => 'unit',
                'quantity' => 3,
                'condition' => 'New',
                'location' => 'Davao',
                'latitude' => 7.1907,
                'longitude' => 125.4553,
                'description' => 'Portable pump for vegetable rows, nurseries, and small orchard blocks.',
                'image_url' => '/assets/farmer-market.png',
                'is_featured' => true,
                'moderation_status' => 'approved',
                'cancellation_policy_id' => $standardPolicy->id,
            ],
            [
                'title' => 'Organic Eggplant Bundle',
                'category' => 'goods',
                'transaction_type' => 'sale',
                'price' => 12,
                'unit' => 'bundle',
                'quantity' => 140,
                'condition' => 'Fresh harvest',
                'location' => 'Pangasinan',
                'latitude' => 15.8949,
                'longitude' => 120.2863,
                'harvest_date' => now(),
                'freshness_expires_at' => now()->addDays(3),
                'description' => 'Naturally grown eggplants packed for wet-market and restaurant orders.',
                'image_url' => '/assets/produce-crates.png',
                'is_featured' => true,
                'report_count' => 4,
                'moderation_status' => 'pending',
                'flagged_reason' => 'Reported by more than 3 users.',
                'cancellation_policy_id' => $standardPolicy->id,
            ],
        ];

        foreach ($items as $item) {
            MarketplaceItem::firstOrCreate(
                ['title' => $item['title'], 'owner_id' => $owner->id],
                array_merge($item, [
                    'owner_id' => $owner->id,
                    'is_available' => true,
                    'geo_hash' => isset($item['latitude'], $item['longitude'])
                        ? sprintf('%+.3f:%+.3f', round((float) $item['latitude'], 3), round((float) $item['longitude'], 3))
                        : null,
                    'listing_score' => (($item['report_count'] ?? 0) > 3) ? 20 : 90,
                    'last_inventory_sync_at' => now(),
                ]),
            );
        }

        $createdItems = MarketplaceItem::query()
            ->where('owner_id', $owner->id)
            ->whereIn('title', array_column($items, 'title'))
            ->get()
            ->keyBy('title');

        foreach ($createdItems as $createdItem) {
            ListingMedia::firstOrCreate(
                ['marketplace_item_id' => $createdItem->id, 'path' => $createdItem->image_url],
                [
                    'type' => 'image',
                    'disk' => 'public',
                    'url' => $createdItem->image_url,
                    'alt_text' => $createdItem->title,
                    'moderation_status' => $createdItem->moderation_status === 'approved' ? 'approved' : 'pending',
                ],
            );
        }

        EquipmentMaintenanceLog::firstOrCreate(
            ['marketplace_item_id' => $createdItems['Rice Combine Harvester']->id, 'maintenance_type' => 'Blade and belt inspection'],
            [
                'performed_by' => $owner->id,
                'status' => 'scheduled',
                'due_date' => now()->addDays(4)->toDateString(),
                'cost_amount' => 85,
                'notes' => 'Pre-season safety check before high-density rentals.',
            ],
        );

        FraudSignal::firstOrCreate(
            [
                'signalable_type' => $createdItems['Organic Eggplant Bundle']->getMorphClass(),
                'signalable_id' => $createdItems['Organic Eggplant Bundle']->id,
                'type' => 'listing_report_velocity',
            ],
            [
                'user_id' => $owner->id,
                'score' => 85,
                'severity' => 'high',
                'reason' => 'Seeded listing exceeded the automated report threshold.',
                'metadata' => ['report_count' => 4],
            ],
        );

        SupportTicket::firstOrCreate(
            ['user_id' => $buyer->id, 'subject' => 'Escrow release timing question'],
            [
                'status' => 'open',
                'priority' => 'normal',
                'latest_message' => 'Buyer asked when funds release after proof of delivery.',
            ],
        );

        $orders = [
            [
                'marketplace_item_id' => $createdItems['Fresh Tomato Crates']->id,
                'user_id' => $buyer->id,
                'quantity' => 16,
                'contact_phone' => $buyer->phone,
                'contact_email' => $buyer->email,
                'message' => 'Please reserve tomato crates for restaurant delivery this week.',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'marketplace_item_id' => $createdItems['Rice Combine Harvester']->id,
                'user_id' => $cooperative->id,
                'quantity' => 2,
                'start_date' => now()->addDays(2)->toDateString(),
                'end_date' => now()->addDays(4)->toDateString(),
                'contact_phone' => $cooperative->phone,
                'contact_email' => $cooperative->email,
                'message' => 'Requesting harvester availability for two days across cooperative fields.',
                'status' => 'approved',
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'marketplace_item_id' => $createdItems['Organic Eggplant Bundle']->id,
                'user_id' => $buyer->id,
                'quantity' => 40,
                'contact_phone' => $buyer->phone,
                'contact_email' => $buyer->email,
                'message' => 'Need eggplant bundle pickup for weekend market.',
                'status' => 'fulfilled',
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(2),
            ],
            [
                'marketplace_item_id' => $createdItems['Compact Irrigation Pump']->id,
                'user_id' => $cooperative->id,
                'quantity' => 1,
                'contact_phone' => $cooperative->phone,
                'contact_email' => $cooperative->email,
                'message' => 'Comparing pump models for a nursery expansion.',
                'status' => 'cancelled',
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(5),
            ],
        ];

        foreach ($orders as $order) {
            Inquiry::firstOrCreate(
                [
                    'marketplace_item_id' => $order['marketplace_item_id'],
                    'user_id' => $order['user_id'],
                    'message' => $order['message'],
                ],
                $order,
            );
        }
    }

    private function seedAccessControl(array $users): void
    {
        $permissionRows = [
            ['name' => 'listings.moderate', 'label' => 'Moderate listings', 'group' => 'moderation'],
            ['name' => 'orders.manage', 'label' => 'Manage orders', 'group' => 'commerce'],
            ['name' => 'wallets.manage', 'label' => 'Manage wallets', 'group' => 'finance'],
            ['name' => 'kyc.review', 'label' => 'Review KYC', 'group' => 'trust'],
            ['name' => 'reports.manage', 'label' => 'Manage reports', 'group' => 'trust'],
        ];

        foreach ($permissionRows as $permission) {
            Permission::firstOrCreate(['name' => $permission['name']], $permission);
        }

        $rolePermissions = [
            User::ROLE_ADMIN => ['*'],
            User::ROLE_FARMER => ['listings.manage', 'orders.respond', 'wallets.view'],
            User::ROLE_BUYER => ['orders.create', 'reviews.create', 'reports.create'],
            User::ROLE_COOPERATIVE => ['listings.manage', 'orders.respond', 'wallets.view', 'members.manage'],
            User::ROLE_DRIVER => ['shipments.manage', 'wallets.view'],
            User::ROLE_INSPECTOR => ['inspections.manage', 'kyc.review', 'reports.manage'],
        ];

        foreach ($rolePermissions as $roleName => $permissions) {
            Role::firstOrCreate(
                ['name' => $roleName],
                ['label' => ucfirst($roleName), 'permissions' => $permissions],
            );
        }

        $allUsers = collect($users)->merge(User::whereIn('role', [User::ROLE_ADMIN, User::ROLE_DRIVER, User::ROLE_INSPECTOR])->get());

        foreach ($allUsers as $user) {
            $role = Role::where('name', $user->role)->first();

            if ($role) {
                $user->roles()->syncWithoutDetaching([$role->id]);
            }
        }
    }

    private function seedWallet(User $user, float $available, float $pending): void
    {
        $wallet = Wallet::firstOrCreate(
            ['user_id' => $user->id, 'currency' => 'USD'],
            [
                'available_balance' => $available,
                'pending_balance' => $pending,
                'held_balance' => 0,
                'status' => 'active',
            ],
        );

        WalletLedgerEntry::firstOrCreate(
            ['wallet_id' => $wallet->id, 'idempotency_key' => 'seed-wallet-'.$user->id],
            [
                'direction' => 'credit',
                'type' => 'seed_balance',
                'amount' => $available + $pending,
                'balance_after' => $available,
                'status' => 'posted',
                'metadata' => ['pending_balance' => $pending],
            ],
        );
    }
}
