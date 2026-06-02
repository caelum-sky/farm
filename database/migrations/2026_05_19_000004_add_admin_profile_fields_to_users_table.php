<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('theme')->default('harvest')->after('role');
            $table->string('dashboard_range', 12)->default('7')->after('theme');
            $table->text('profile_notes')->nullable()->after('dashboard_range');
            $table->boolean('notification_email')->default(true)->after('profile_notes');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'username',
                'theme',
                'dashboard_range',
                'profile_notes',
                'notification_email',
            ]);
        });
    }
};
