<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('label');
            $table->unsignedTinyInteger('free_cancellation_hours')->default(24);
            $table->decimal('seller_no_show_penalty_rate', 5, 2)->default(0);
            $table->decimal('buyer_late_cancel_penalty_rate', 5, 2)->default(0);
            $table->text('terms')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::table('marketplace_items', function (Blueprint $table): void {
            $table->foreignId('cancellation_policy_id')->nullable()->after('cancellation_policy')->constrained()->nullOnDelete();
            $table->string('geo_hash', 24)->nullable()->after('longitude')->index();
            $table->timestamp('freshness_expires_at')->nullable()->after('harvest_date')->index();
            $table->unsignedTinyInteger('listing_score')->default(50)->after('report_count')->index();
            $table->timestamp('last_inventory_sync_at')->nullable()->after('reserved_quantity');
            $table->timestamp('legal_hold_until')->nullable()->after('deleted_at')->index();
            $table->index(['geo_hash', 'category', 'transaction_type'], 'marketplace_geo_category_index');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('legal_hold_until')->nullable()->after('deleted_at')->index();
            $table->string('payout_provider_account_id')->nullable()->after('two_factor_confirmed_at')->index();
            $table->string('tax_status', 32)->default('uncollected')->after('payout_provider_account_id')->index();
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('invoice_number')->nullable()->unique()->after('order_number');
            $table->timestamp('invoice_issued_at')->nullable()->after('currency');
            $table->timestamp('proof_of_delivery_at')->nullable()->after('fulfilled_at');
            $table->timestamp('refund_deadline_at')->nullable()->after('cancelled_at')->index();
            $table->string('cancellation_reason')->nullable()->after('refund_deadline_at');
            $table->unsignedInteger('version')->default(1)->after('cancellation_reason');
            $table->timestamp('legal_hold_until')->nullable()->after('deleted_at')->index();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->foreignId('operator_user_id')->nullable()->after('owner_id')->constrained('users')->nullOnDelete();
            $table->foreignId('pickup_inspection_id')->nullable()->after('operator_user_id')->constrained('inspection_reports')->nullOnDelete();
            $table->foreignId('return_inspection_id')->nullable()->after('pickup_inspection_id')->constrained('inspection_reports')->nullOnDelete();
            $table->decimal('damage_claim_amount', 14, 2)->default(0)->after('late_fee_amount');
            $table->decimal('late_fee_daily_amount', 14, 2)->default(0)->after('damage_claim_amount');
            $table->timestamp('legal_hold_until')->nullable()->after('deleted_at')->index();
        });

        Schema::create('booking_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('marketplace_item_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32)->default('reserved')->index();
            $table->decimal('quantity', 12, 2);
            $table->decimal('rate_amount', 14, 2)->default(0);
            $table->decimal('line_total_amount', 14, 2)->default(0);
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['marketplace_item_id', 'status', 'starts_at', 'ends_at'], 'booking_lines_overlap_index');
        });

        Schema::create('fraud_signals', function (Blueprint $table): void {
            $table->id();
            $table->nullableMorphs('signalable');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 80)->index();
            $table->unsignedTinyInteger('score')->default(0)->index();
            $table->string('severity', 24)->default('low')->index();
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->string('idempotency_key')->nullable()->unique()->after('provider_reference');
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->string('idempotency_key')->nullable()->unique()->after('reason');
        });

        Schema::table('payouts', function (Blueprint $table): void {
            $table->string('idempotency_key')->nullable()->unique()->after('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table): void {
            $table->dropColumn('idempotency_key');
        });

        Schema::table('refunds', function (Blueprint $table): void {
            $table->dropColumn('idempotency_key');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn('idempotency_key');
        });

        Schema::dropIfExists('fraud_signals');
        Schema::dropIfExists('booking_lines');

        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('operator_user_id');
            $table->dropConstrainedForeignId('pickup_inspection_id');
            $table->dropConstrainedForeignId('return_inspection_id');
            $table->dropColumn([
                'damage_claim_amount',
                'late_fee_daily_amount',
                'legal_hold_until',
            ]);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'invoice_number',
                'invoice_issued_at',
                'proof_of_delivery_at',
                'refund_deadline_at',
                'cancellation_reason',
                'version',
                'legal_hold_until',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'legal_hold_until',
                'payout_provider_account_id',
                'tax_status',
            ]);
        });

        Schema::table('marketplace_items', function (Blueprint $table): void {
            $table->dropIndex('marketplace_geo_category_index');
            $table->dropConstrainedForeignId('cancellation_policy_id');
            $table->dropColumn([
                'geo_hash',
                'freshness_expires_at',
                'listing_score',
                'last_inventory_sync_at',
                'legal_hold_until',
            ]);
        });

        Schema::dropIfExists('cancellation_policies');
    }
};
