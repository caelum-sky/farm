<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type', 32)->default('cooperative')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('registration_number')->nullable()->index();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('region')->nullable()->index();
            $table->text('verification_notes')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('organization_id')->nullable()->after('id')->constrained('organizations')->nullOnDelete();
            $table->string('status', 32)->default('active')->index()->after('role');
            $table->string('kyc_status', 32)->default('unverified')->index()->after('status');
            $table->unsignedTinyInteger('risk_score')->default(0)->index()->after('kyc_status');
            $table->timestamp('last_login_at')->nullable()->after('remember_token');
            $table->softDeletes();
        });

        Schema::create('organization_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 32)->default('member')->index();
            $table->string('status', 32)->default('active')->index();
            $table->json('permissions')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
        });

        Schema::table('marketplace_items', function (Blueprint $table): void {
            $table->string('lifecycle_status', 32)->default('active')->index()->after('moderation_status');
            $table->timestamp('published_at')->nullable()->index()->after('flagged_reason');
            $table->decimal('latitude', 10, 7)->nullable()->after('location');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->decimal('reserved_quantity', 12, 2)->default(0)->after('quantity');
            $table->decimal('min_order_quantity', 12, 2)->default(0.01)->after('reserved_quantity');
            $table->decimal('max_order_quantity', 12, 2)->nullable()->after('min_order_quantity');
            $table->decimal('deposit_amount', 12, 2)->default(0)->after('rent_rate');
            $table->string('cancellation_policy', 32)->default('standard')->after('deposit_amount');
            $table->unsignedInteger('version')->default(1)->after('cancellation_policy');
            $table->softDeletes();
            $table->index(['category', 'transaction_type', 'is_available', 'moderation_status'], 'marketplace_public_lookup_index');
            $table->index(['owner_id', 'lifecycle_status']);
        });

        Schema::table('inquiries', function (Blueprint $table): void {
            $table->string('type', 32)->default('inquiry')->index()->after('id');
            $table->decimal('subtotal_amount', 12, 2)->default(0)->after('message');
            $table->decimal('tax_amount', 12, 2)->default(0)->after('subtotal_amount');
            $table->decimal('platform_fee_amount', 12, 2)->default(0)->after('tax_amount');
            $table->decimal('escrow_amount', 12, 2)->default(0)->after('platform_fee_amount');
            $table->string('payment_status', 32)->default('unpaid')->index()->after('escrow_amount');
            $table->string('escrow_status', 32)->default('none')->index()->after('payment_status');
            $table->string('dispute_status', 32)->default('none')->index()->after('escrow_status');
            $table->timestamp('seller_accepted_at')->nullable()->after('dispute_status');
            $table->timestamp('buyer_confirmed_at')->nullable()->after('seller_accepted_at');
            $table->timestamp('fulfilled_at')->nullable()->after('buyer_confirmed_at');
            $table->timestamp('cancelled_at')->nullable()->after('fulfilled_at');
            $table->timestamp('expires_at')->nullable()->index()->after('cancelled_at');
            $table->timestamp('return_due_at')->nullable()->index()->after('expires_at');
            $table->unsignedInteger('version')->default(1)->after('return_due_at');
            $table->softDeletes();
            $table->index(['marketplace_item_id', 'status', 'start_date', 'end_date'], 'inquiry_booking_overlap_index');
            $table->index(['user_id', 'status', 'created_at']);
        });

        Schema::create('listing_availability_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default('blocked')->index();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->string('reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['marketplace_item_id', 'starts_at', 'ends_at'], 'listing_availability_range_index');
        });

        Schema::create('wallets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('USD');
            $table->decimal('available_balance', 14, 2)->default(0);
            $table->decimal('pending_balance', 14, 2)->default(0);
            $table->decimal('held_balance', 14, 2)->default(0);
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
            $table->unique(['user_id', 'currency']);
            $table->index(['organization_id', 'currency']);
        });

        Schema::create('wallet_ledger_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('ledgerable');
            $table->string('direction', 16);
            $table->string('type', 48)->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('balance_after', 14, 2)->default(0);
            $table->string('status', 32)->default('posted')->index();
            $table->string('idempotency_key')->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'created_at']);
        });

        Schema::create('payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('status', 32)->default('pending')->index();
            $table->string('provider')->nullable();
            $table->string('provider_reference')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('disputes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 48)->index();
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 24)->default('normal')->index();
            $table->text('summary');
            $table->json('evidence')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('reviewee_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('marketplace_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('rating');
            $table->text('body')->nullable();
            $table->string('status', 32)->default('published')->index();
            $table->timestamps();
            $table->unique(['inquiry_id', 'reviewer_id']);
        });

        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('reportable');
            $table->string('reason', 80)->index();
            $table->text('details')->nullable();
            $table->string('status', 32)->default('open')->index();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->nullableMorphs('ticketable');
            $table->string('subject');
            $table->string('status', 32)->default('open')->index();
            $table->string('priority', 24)->default('normal')->index();
            $table->text('latest_message')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('equipment_maintenance_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketplace_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('scheduled')->index();
            $table->string('maintenance_type', 80);
            $table->date('due_date')->nullable()->index();
            $table->date('completed_date')->nullable();
            $table->decimal('cost_amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('inspection_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marketplace_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 32)->default('pre_rental')->index();
            $table->string('status', 32)->default('pending')->index();
            $table->json('checklist')->nullable();
            $table->json('photos')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('app_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('channel', 32)->default('database')->index();
            $table->string('type', 80)->index();
            $table->string('status', 32)->default('pending')->index();
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
        Schema::dropIfExists('inspection_reports');
        Schema::dropIfExists('equipment_maintenance_logs');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('reviews');
        Schema::dropIfExists('disputes');
        Schema::dropIfExists('payouts');
        Schema::dropIfExists('wallet_ledger_entries');
        Schema::dropIfExists('wallets');
        Schema::dropIfExists('listing_availability_blocks');

        Schema::table('inquiries', function (Blueprint $table): void {
            $table->dropIndex('inquiry_booking_overlap_index');
            $table->dropIndex(['user_id', 'status', 'created_at']);
            $table->dropColumn([
                'type',
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
                'deleted_at',
            ]);
        });

        Schema::table('marketplace_items', function (Blueprint $table): void {
            $table->dropIndex('marketplace_public_lookup_index');
            $table->dropIndex(['owner_id', 'lifecycle_status']);
            $table->dropColumn([
                'lifecycle_status',
                'published_at',
                'latitude',
                'longitude',
                'reserved_quantity',
                'min_order_quantity',
                'max_order_quantity',
                'deposit_amount',
                'cancellation_policy',
                'version',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('organization_members');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('organization_id');
            $table->dropColumn([
                'status',
                'kyc_status',
                'risk_score',
                'last_login_at',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('organizations');
    }
};
