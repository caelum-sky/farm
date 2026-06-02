<?php

namespace Tests\Feature;

use App\Models\MarketplaceItem;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MarketplaceRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_loads(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('FarmBridge')
            ->assertSee('Featured farm listings');
    }

    public function test_auth_pages_load(): void
    {
        $this->get('/login')->assertOk()->assertSee('Open your FarmBridge account');
        $this->get('/signup')->assertOk()->assertSee('Create your account');
        $this->get('/forgot-password')->assertOk()->assertSee('Send a reset link');
    }

    public function test_registration_sends_email_verification_and_password_reset_can_be_requested(): void
    {
        Notification::fake();

        $this->post('/signup', [
            'name' => 'Verification User',
            'farm_name' => 'Verification Farm',
            'email' => 'verify@farmbridge.test',
            'phone' => '+63 900 000 9000',
            'location' => 'Cavite',
            'role' => 'buyer',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('dashboard'));

        $user = User::where('email', 'verify@farmbridge.test')->firstOrFail();
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->post('/forgot-password', [
            'email' => 'verify@farmbridge.test',
        ])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_seeded_admin_account_can_login(): void
    {
        $this->seed(\Database\Seeders\AdminSeeder::class);

        $this->post('/login', [
            'email' => 'admin@farmbridge.test',
            'password' => 'admin12345',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Admin dashboard')
            ->assertSee('Manage FarmBridge')
            ->assertSee('Pivot table');
    }

    public function test_user_profile_settings_and_phone_otp_work(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('private');
        $buyer = User::where('email', 'buyer@farmbridge.test')->firstOrFail();

        $this->actingAs($buyer);

        $this->get('/profile')
            ->assertOk()
            ->assertSee('Profile settings')
            ->assertSee('Phone OTP');

        $this->patch('/profile', [
            'name' => 'Juan Profile Updated',
            'username' => 'juan-profile',
            'farm_name' => 'Reyes Grocers',
            'bio' => 'Buyer of regional produce.',
            'email' => 'buyer-updated@farmbridge.test',
            'phone' => '+63 900 000 3333',
            'location' => 'Laguna',
            'address' => 'Calamba, Laguna',
            'gender' => 'prefer_not_to_say',
            'birthdate' => '1990-01-01',
            'theme' => 'field',
            'dashboard_range' => '3',
            'notification_email' => '1',
            'profile_visibility' => 'marketplace',
            'share_location' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $buyer->id,
            'email' => 'buyer-updated@farmbridge.test',
            'phone_verified_at' => null,
            'theme' => 'field',
        ]);

        $buyer->refresh()->forceFill([
            'phone_verification_code_hash' => Hash::make('123456'),
            'phone_verification_expires_at' => now()->addMinutes(10),
            'phone_verification_attempts' => 0,
        ])->save();

        $this->post('/profile/phone/verify', [
            'otp_code' => '123456',
        ])->assertRedirect();

        $this->assertNotNull($buyer->fresh()->phone_verified_at);

        $this->post('/profile/identity', [
            'document_type' => 'national_id',
            'identity_document' => $this->fakePng('validated-id.png'),
            'notes' => 'Test identity document.',
        ])->assertRedirect();

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $buyer->id,
            'status' => 'pending',
            'document_type' => 'national_id',
        ]);
        $this->assertDatabaseHas('documents', [
            'uploaded_by' => $buyer->id,
            'type' => 'national_id',
            'disk' => 'private',
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_manage_users_posts_orders_and_settings(): void
    {
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@farmbridge.test')->firstOrFail();

        $this->actingAs($admin);

        $this->get('/admin/users')->assertOk()->assertSee('User management');
        $this->get('/admin/posts')->assertOk()->assertSee('Post management');
        $this->get('/admin/orders')->assertOk()->assertSee('Order management');
        $this->get('/admin/settings')->assertOk()->assertSee('Admin settings');
        $this->get('/admin/audit')->assertOk()->assertSee('Audit trail');
        $this->get('/admin/export/users')->assertOk();
        $this->get('/admin/export/posts')->assertOk();
        $this->get('/admin/export/orders')->assertOk();
        $this->get('/admin/export/audit')->assertOk();

        $this->post('/admin/users', [
            'name' => 'Test Buyer',
            'username' => 'test-buyer',
            'farm_name' => 'Test Market',
            'email' => 'test-buyer@farmbridge.test',
            'phone' => '+63 900 000 1111',
            'location' => 'Cavite',
            'role' => 'buyer',
            'theme' => 'field',
            'dashboard_range' => '3',
            'profile_notes' => 'Created from admin test.',
            'notification_email' => '1',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('admin.users.index'));

        $managedUser = User::where('email', 'test-buyer@farmbridge.test')->firstOrFail();

        $this->patch('/admin/users/'.$managedUser->id, [
            'name' => 'Updated Buyer',
            'username' => 'updated-buyer',
            'farm_name' => 'Updated Market',
            'email' => 'test-buyer@farmbridge.test',
            'phone' => '+63 900 000 2222',
            'location' => 'Batangas',
            'role' => 'buyer',
            'theme' => 'sunset',
            'dashboard_range' => '7',
            'profile_notes' => 'Updated from admin test.',
            'notification_email' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'test-buyer@farmbridge.test',
            'name' => 'Updated Buyer',
            'theme' => 'sunset',
        ]);

        $this->patch('/admin/users/'.$managedUser->id.'/role', [
            'role' => 'cooperative',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'test-buyer@farmbridge.test',
            'role' => 'cooperative',
        ]);

        $this->post('/admin/posts', [
            'owner_id' => $managedUser->id,
            'title' => 'Admin Test Pump',
            'category' => 'equipment',
            'transaction_type' => 'sale',
            'price' => 125,
            'unit' => 'unit',
            'quantity' => 2,
            'condition' => 'New',
            'location' => 'Batangas',
            'description' => 'A test listing managed by the admin panel.',
            'is_available' => '1',
        ])->assertRedirect(route('admin.posts.index'));

        $post = MarketplaceItem::where('title', 'Admin Test Pump')->firstOrFail();

        $this->patch('/admin/posts/'.$post->id, [
            'owner_id' => $managedUser->id,
            'title' => 'Admin Test Pump Updated',
            'category' => 'equipment',
            'transaction_type' => 'rent',
            'rent_rate' => 35,
            'unit' => 'day',
            'quantity' => 2,
            'condition' => 'New',
            'location' => 'Batangas',
            'description' => 'A rental listing managed by the admin panel.',
            'is_featured' => '1',
            'is_available' => '1',
        ])->assertRedirect(route('admin.posts.index'));

        $post->refresh();
        $this->assertTrue($post->isRental());
        $this->assertTrue($post->is_featured);

        $this->patch('/admin/posts/'.$post->id.'/moderate', [
            'action' => 'hide',
        ])->assertRedirect();

        $this->assertDatabaseHas('marketplace_items', [
            'id' => $post->id,
            'is_available' => false,
        ]);

        $this->post('/admin/orders', [
            'marketplace_item_id' => $post->id,
            'user_id' => $managedUser->id,
            'quantity' => 1,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'contact_phone' => '+63 900 000 2222',
            'contact_email' => 'test-buyer@farmbridge.test',
            'message' => 'Admin-created order.',
            'status' => 'pending',
        ])->assertRedirect(route('admin.orders.index'));

        $orderId = $post->inquiries()->where('message', 'Admin-created order.')->value('id');
        $this->assertNotNull($orderId);

        $this->patch('/admin/orders/'.$orderId, [
            'marketplace_item_id' => $post->id,
            'user_id' => $managedUser->id,
            'quantity' => 1,
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'contact_phone' => '+63 900 000 2222',
            'contact_email' => 'test-buyer@farmbridge.test',
            'message' => 'Admin-updated order.',
            'status' => 'approved',
        ])->assertRedirect(route('admin.orders.index'));

        $this->assertDatabaseHas('inquiries', [
            'id' => $orderId,
            'status' => 'approved',
        ]);

        $this->patch('/admin/orders/'.$orderId.'/status', [
            'status' => 'fulfilled',
        ])->assertRedirect();

        $this->assertDatabaseHas('inquiries', [
            'id' => $orderId,
            'status' => 'fulfilled',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'status_changed',
            'subject_id' => $orderId,
        ]);

        $this->patch('/admin/settings', [
            'name' => 'FarmBridge Admin Updated',
            'username' => 'admin-updated',
            'email' => 'admin@farmbridge.test',
            'farm_name' => 'FarmBridge Operations',
            'phone' => '+63 917 000 0101',
            'location' => 'Manila',
            'theme' => 'night',
            'dashboard_range' => '30',
            'profile_notes' => 'Admin settings test.',
            'notification_email' => '1',
            'homepage_headline' => 'Admin controlled headline',
            'homepage_copy' => 'Admin controlled landing copy for FarmBridge.',
            'site_announcement' => 'Testing saved site announcement.',
            'marketplace_status' => 'limited',
            'maintenance_mode' => '1',
            'orders_enabled' => '1',
            'rentals_enabled' => '1',
            'global_tax_rate' => '4.5',
            'platform_fee_rate' => '3.25',
            'listing_review_required' => '1',
            'max_active_inquiries_per_user' => '25',
            'support_email' => 'ops@farmbridge.test',
            'flagged_keywords' => 'unsafe, scam, testflag',
        ])->assertRedirect(route('admin.settings.edit'));

        $admin->refresh();
        $this->assertSame('night', $admin->theme);
        $this->assertSame('Admin controlled headline', SiteSetting::allSettings()['homepage_headline']);
        $this->assertSame('4.5', SiteSetting::allSettings()['global_tax_rate']);
        $this->assertSame('3.25', SiteSetting::allSettings()['platform_fee_rate']);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'settings_updated',
            'subject_id' => $admin->id,
        ]);

        $this->delete('/admin/orders/'.$orderId)->assertRedirect(route('admin.orders.index'));
        $this->delete('/admin/posts/'.$post->id)->assertRedirect(route('admin.posts.index'));
        $this->delete('/admin/users/'.$managedUser->id)->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['email' => 'test-buyer@farmbridge.test']);
    }

    public function test_marketplace_blocks_hidden_own_and_overbooked_requests(): void
    {
        $this->seed(DatabaseSeeder::class);

        $owner = User::where('email', 'demo@farmbridge.test')->firstOrFail();
        $buyer = User::where('email', 'buyer@farmbridge.test')->firstOrFail();
        $listing = MarketplaceItem::where('title', 'Fresh Tomato Crates')->firstOrFail();

        $this->actingAs($owner);
        $this->post(route('marketplace.inquire', $listing), [
            'quantity' => 1,
            'contact_email' => $owner->email,
            'message' => 'Trying to request my own listing.',
        ])->assertSessionHasErrors('listing');

        $this->actingAs($buyer);
        $this->post(route('marketplace.inquire', $listing), [
            'quantity' => 10000,
            'contact_email' => $buyer->email,
            'message' => 'Trying to overbook inventory.',
        ])->assertSessionHasErrors('quantity');

        $listing->update([
            'is_available' => false,
            'lifecycle_status' => MarketplaceItem::LIFECYCLE_HIDDEN,
        ]);

        $this->get(route('marketplace.show', $listing))->assertNotFound();
    }

    public function test_rental_date_overlap_is_rejected(): void
    {
        $this->seed(DatabaseSeeder::class);

        $buyer = User::where('email', 'buyer@farmbridge.test')->firstOrFail();
        $rental = MarketplaceItem::where('title', 'Rice Combine Harvester')->firstOrFail();

        Inquiry::create([
            'type' => 'booking_request',
            'marketplace_item_id' => $rental->id,
            'user_id' => $buyer->id,
            'quantity' => 1,
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
            'contact_email' => $buyer->email,
            'message' => 'Existing booking hold.',
            'status' => Inquiry::STATUS_APPROVED,
            'payment_status' => 'pending',
            'escrow_status' => 'awaiting_funding',
        ]);

        $this->actingAs($buyer);
        $this->post(route('marketplace.inquire', $rental), [
            'quantity' => 1,
            'start_date' => now()->addDays(11)->toDateString(),
            'end_date' => now()->addDays(13)->toDateString(),
            'contact_email' => $buyer->email,
            'message' => 'Overlapping rental request.',
        ])->assertSessionHasErrors('quantity');
    }

    public function test_user_created_listings_require_review_before_public_listing(): void
    {
        $this->seed(DatabaseSeeder::class);
        Storage::fake('public');

        $seller = User::where('email', 'demo@farmbridge.test')->firstOrFail();
        $this->actingAs($seller);

        $this->post(route('marketplace.store'), [
            'title' => 'Review Required Listing',
            'category' => 'goods',
            'transaction_type' => 'sale',
            'price' => 25,
            'unit' => 'crate',
            'quantity' => 5,
            'location' => 'Laguna',
            'description' => 'A listing that should wait for review.',
            'product_photo' => $this->fakePng('review-listing.png'),
        ])->assertRedirect();

        $post = MarketplaceItem::where('title', 'Review Required Listing')->firstOrFail();

        $this->assertSame('pending', $post->moderation_status);
        $this->assertSame(MarketplaceItem::LIFECYCLE_REVIEW, $post->lifecycle_status);
        $this->assertFalse($post->is_available);
        $this->assertDatabaseHas('listing_media', [
            'marketplace_item_id' => $post->id,
            'disk' => 'public',
            'moderation_status' => 'pending',
        ]);

        $this->get('/marketplace')->assertDontSee('Review Required Listing');
    }

    public function test_api_login_checkout_wallet_and_notifications_work(): void
    {
        $this->seed(DatabaseSeeder::class);

        $buyer = User::where('email', 'buyer@farmbridge.test')->firstOrFail();
        $listing = MarketplaceItem::where('title', 'Fresh Tomato Crates')->firstOrFail();

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@farmbridge.test',
            'password' => 'password',
        ])->assertOk()
            ->assertJsonPath('user.email', 'buyer@farmbridge.test');

        $token = $login->json('access_token');
        $headers = ['Authorization' => 'Bearer '.$token, 'X-Device-Fingerprint' => 'test-device-1'];

        $this->withHeaders($headers)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $buyer->id);

        $checkout = $this->withHeaders($headers)
            ->postJson('/api/v1/listings/'.$listing->id.'/checkout', [
                'quantity' => 3,
                'contact_email' => $buyer->email,
                'message' => 'API checkout for restaurant tomato crates.',
                'delivery_method' => 'pickup',
            ])->assertCreated()
            ->assertJsonPath('data.status', Order::STATUS_ESCROW_HELD);

        $orderId = $checkout->json('data.id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'buyer_id' => $buyer->id,
            'status' => Order::STATUS_ESCROW_HELD,
        ]);
        $this->assertDatabaseHas('payments', ['order_id' => $orderId, 'status' => 'authorized']);
        $this->assertDatabaseHas('escrow_transactions', ['order_id' => $orderId, 'status' => 'held']);
        $this->assertDatabaseHas('wallet_ledger_entries', ['type' => 'escrow_pending']);
        $this->assertDatabaseHas('app_notifications', ['user_id' => $buyer->id, 'type' => 'escrow.held']);
        $this->assertDatabaseHas('devices', ['fingerprint' => 'test-device-1', 'user_id' => $buyer->id]);

        $this->withHeaders($headers)
            ->getJson('/api/v1/wallet')
            ->assertOk()
            ->assertJsonStructure(['data' => ['available_balance', 'pending_balance', 'ledger']]);
    }

    public function test_api_webhooks_are_idempotent(): void
    {
        $this->seed(DatabaseSeeder::class);

        $payload = ['id' => 'evt_test_1', 'type' => 'payment_intent.succeeded'];

        $this->postJson('/api/v1/webhooks/stripe', $payload)->assertAccepted();
        $this->postJson('/api/v1/webhooks/stripe', $payload)->assertStatus(202);

        $this->assertDatabaseCount('webhook_events', 1);
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'stripe',
            'event_id' => 'evt_test_1',
        ]);
    }

    public function test_api_security_controls_reject_unsigned_webhooks_and_expired_tokens(): void
    {
        $this->seed(DatabaseSeeder::class);

        Config::set('farmbridge_security.webhooks.require_signatures', true);
        Config::set('farmbridge_security.webhooks.secrets.stripe', 'test-webhook-secret');

        $payload = ['id' => 'evt_signed_1', 'type' => 'payment_intent.succeeded'];
        $rawPayload = json_encode($payload);
        $signature = 'sha256='.hash_hmac('sha256', $rawPayload, 'test-webhook-secret');

        $this->postJson('/api/v1/webhooks/stripe', $payload)->assertUnauthorized();
        $this->withHeaders(['X-FarmBridge-Signature' => $signature])
            ->postJson('/api/v1/webhooks/stripe', $payload)
            ->assertAccepted();

        Config::set('farmbridge_security.api_token_ttl_minutes', 1);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@farmbridge.test',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('access_token');
        $buyer = User::where('email', 'buyer@farmbridge.test')->firstOrFail();
        $buyer->forceFill(['api_token_created_at' => now()->subMinutes(5)])->save();

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'API token expired. Please sign in again.');

        $this->assertNull($buyer->fresh()->api_token_hash);
    }

    public function test_deployment_health_cart_dispute_refund_and_support_flows_work(): void
    {
        $this->seed(DatabaseSeeder::class);

        $listing = MarketplaceItem::where('title', 'Fresh Tomato Crates')->firstOrFail();

        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertJsonPath('status', 'ready')
            ->assertJsonPath('checks.database', 'ok');

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'buyer@farmbridge.test',
            'password' => 'password',
        ])->assertOk();

        $headers = ['Authorization' => 'Bearer '.$login->json('access_token')];

        $this->withHeaders($headers)
            ->postJson('/api/v1/cart/items/'.$listing->id, [
                'quantity' => 2,
            ])->assertCreated()
            ->assertJsonPath('data.status', 'active');

        $cartCheckout = $this->withHeaders($headers)
            ->postJson('/api/v1/cart/checkout', [
                'contact_email' => 'buyer@farmbridge.test',
                'message' => 'Cart checkout for deployment readiness test.',
            ])->assertCreated()
            ->assertJsonPath('data.0.status', Order::STATUS_ESCROW_HELD);

        $orderId = $cartCheckout->json('data.0.id');

        $this->withHeaders($headers)
            ->postJson('/api/v1/orders/'.$orderId.'/disputes', [
                'type' => 'quality',
                'summary' => 'Produce quality issue opened from mobile workflow.',
            ])->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->withHeaders($headers)
            ->postJson('/api/v1/orders/'.$orderId.'/refunds', [
                'amount' => 1,
                'reason' => 'Goodwill partial refund.',
            ])->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->withHeaders($headers)
            ->postJson('/api/v1/support-tickets', [
                'subject' => 'Need help with delivery proof',
                'latest_message' => 'The buyer needs help uploading proof details.',
            ])->assertCreated()
            ->assertJsonPath('data.status', 'open');

        $this->withHeaders($headers)
            ->postJson('/api/v1/me/kyc', [
                'document_type' => 'farm_registration',
                'document_path' => 'kyc/demo-registration.pdf',
            ])->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('disputes', ['type' => 'quality', 'status' => 'open']);
        $this->assertDatabaseHas('refunds', ['order_id' => $orderId, 'status' => 'pending']);
        $this->assertDatabaseHas('support_tickets', ['subject' => 'Need help with delivery proof']);
        $this->assertDatabaseHas('kyc_verifications', ['document_type' => 'farm_registration', 'status' => 'pending']);
    }

    private function fakePng(string $name): UploadedFile
    {
        $png = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII='
        );

        return UploadedFile::fake()->createWithContent($name, $png);
    }
}
