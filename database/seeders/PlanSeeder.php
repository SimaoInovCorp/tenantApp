<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the four base plans.
     *
     * Run with: php artisan db:seed --class=PlanSeeder
     * Uses updateOrCreate so re-running is safe (idempotent).
     */
    public function run(): void
    {
        $plans = [
            [
                'name'       => 'Free',
                'slug'       => 'free',
                'price'      => 0.00,
                'interval'   => 'monthly',
                'trial_days' => 14,
                'limits'     => ['max_users' => 2],
                'features'   => [],
                'is_active'  => true,
            ],
            [
                'name'       => 'Starter',
                'slug'       => 'starter',
                'price'      => 29.00,
                'interval'   => 'monthly',
                'trial_days' => 14,
                'limits'     => ['max_users' => 5],
                'features'   => ['reports'],
                'is_active'  => true,
            ],
            [
                'name'       => 'Pro',
                'slug'       => 'pro',
                'price'      => 99.00,
                'interval'   => 'monthly',
                'trial_days' => 14,
                'limits'     => ['max_users' => 25],
                'features'   => ['reports', 'api_access'],
                'is_active'  => true,
            ],
            [
                'name'       => 'Enterprise',
                'slug'       => 'enterprise',
                'price'      => 299.00,
                'interval'   => 'monthly',
                'trial_days' => 0,
                'limits'     => ['max_users' => -1],
                'features'   => ['reports', 'api_access', 'custom_branding'],
                'is_active'  => true,
            ],
        ];

        foreach ($plans as $data) {
            Plan::updateOrCreate(['slug' => $data['slug']], $data);
        }

        $this->command->info('Plans seeded: Free, Starter, Pro, Enterprise.');
    }
}
