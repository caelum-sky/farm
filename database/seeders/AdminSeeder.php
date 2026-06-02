<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = env('ADMIN_PASSWORD') ?: 'admin12345';

        if (app()->environment('production') && in_array($adminPassword, ['admin12345', 'password', 'change-this-before-deploy'], true)) {
            throw new RuntimeException('Refusing to seed a production admin account with a default or placeholder password.');
        }

        User::updateOrCreate(
            ['email' => 'admin@farmbridge.test'],
            [
                'name' => 'FarmBridge Admin',
                'username' => 'admin',
                'farm_name' => 'FarmBridge Operations',
                'password' => Hash::make($adminPassword),
                'phone' => '+63 917 000 0101',
                'location' => 'Manila',
                'role' => 'admin',
                'status' => 'active',
                'kyc_status' => 'verified',
                'risk_score' => 0,
                'theme' => 'harvest',
                'dashboard_range' => '7',
                'profile_notes' => 'Primary administrator account for marketplace operations.',
                'notification_email' => true,
                'password_changed_at' => now(),
            ],
        );
    }
}
