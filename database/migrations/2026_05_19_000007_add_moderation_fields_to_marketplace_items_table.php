<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_items', function (Blueprint $table): void {
            $table->unsignedInteger('report_count')->default(0)->after('is_available');
            $table->string('moderation_status', 24)->default('approved')->index()->after('report_count');
            $table->string('flagged_reason', 500)->nullable()->after('moderation_status');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_items', function (Blueprint $table): void {
            $table->dropColumn([
                'report_count',
                'moderation_status',
                'flagged_reason',
            ]);
        });
    }
};
