<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'profile_picture')) {
                $table->string('profile_picture')->nullable()->after('farm_name');
            }

            if (! Schema::hasColumn('users', 'bio')) {
                $table->text('bio')->nullable()->after('profile_picture');
            }

            if (! Schema::hasColumn('users', 'address')) {
                $table->string('address')->nullable()->after('location');
            }

            if (! Schema::hasColumn('users', 'gender')) {
                $table->string('gender', 32)->nullable()->after('address');
            }

            if (! Schema::hasColumn('users', 'birthdate')) {
                $table->date('birthdate')->nullable()->after('gender');
            }

            if (! Schema::hasColumn('users', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'phone_verification_code_hash')) {
                $table->string('phone_verification_code_hash')->nullable()->after('phone_verified_at');
            }

            if (! Schema::hasColumn('users', 'phone_verification_expires_at')) {
                $table->timestamp('phone_verification_expires_at')->nullable()->after('phone_verification_code_hash');
            }

            if (! Schema::hasColumn('users', 'phone_verification_attempts')) {
                $table->unsignedTinyInteger('phone_verification_attempts')->default(0)->after('phone_verification_expires_at');
            }

            if (! Schema::hasColumn('users', 'profile_visibility')) {
                $table->string('profile_visibility', 24)->default('marketplace')->after('notification_email');
            }

            if (! Schema::hasColumn('users', 'share_location')) {
                $table->boolean('share_location')->default(true)->after('profile_visibility');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $columns = [
                'profile_picture',
                'bio',
                'address',
                'gender',
                'birthdate',
                'phone_verified_at',
                'phone_verification_code_hash',
                'phone_verification_expires_at',
                'phone_verification_attempts',
                'profile_visibility',
                'share_location',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
