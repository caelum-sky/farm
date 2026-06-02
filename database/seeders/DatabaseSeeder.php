<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        SiteSetting::putMany(SiteSetting::defaults());
        $this->call(AdminSeeder::class);
        $this->call(MarketplaceSeeder::class);

        $admin = User::query()->where('email', 'admin@farmbridge.test')->first();

        if ($admin) {
            AuditLog::firstOrCreate(
                ['action' => 'seeded', 'summary' => 'Demo marketplace data was seeded.'],
                [
                    'user_id' => $admin->id,
                    'metadata' => [
                        'users' => User::query()->count(),
                    ],
                ],
            );
        }
    }
}
