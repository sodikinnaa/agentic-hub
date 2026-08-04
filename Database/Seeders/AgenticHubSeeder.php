<?php

namespace Modules\AgenticHub\Database\Seeders;

use App\Models\Feature;
use App\Models\User;
use Illuminate\Database\Seeder;

class AgenticHubSeeder extends Seeder
{
    /**
     * Run the database seeds for AgenticHub module.
     */
    public function run(): void
    {
        // 1. Create or update 'agentic-hub' feature
        $feature = Feature::updateOrCreate(
            ['code' => 'agentic-hub'],
            [
                'name' => 'Agentic Hub — AI Product & Tool Calling Engine',
                'description' => 'Modul independen pengelola katalog produk, harga resmi, link checkout direct, dan Pintu Utama Tool Calling API Engine untuk Model AI.',
                'is_active' => true,
                'allow_trial' => true,
                'allow_weekly' => true,
                'allow_monthly' => true,
                'allow_yearly' => true,
                'allow_lifetime' => true,
                'price_trial' => 0.00,
                'trial_days' => 1,
                'price_weekly' => 15000.00,
                'price_monthly' => 35000.00,
                'price_yearly' => 250000.00,
                'price_lifetime' => 500000.00,
            ]
        );

        // 2. Grant feature to admin users by default
        $admins = User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->features()->syncWithoutDetaching([
                $feature->id => [
                    'is_enabled' => true,
                    'plan_type' => 'lifetime',
                    'expires_at' => null,
                ]
            ]);
        }

        $this->command?->info('Module [Agentic Hub] feature seeded successfully!');
    }
}
