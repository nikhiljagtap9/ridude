<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('subscription_plans')->updateOrInsert(
            [
                'slug' => 'weekly',
            ],
            [
                'name' => 'Weekly Plan',
                'price' => 9.99,
                'billing_cycle' => 'week',
                'trial_days' => 7,
                'currency' => 'USD',
                'is_active' => 1,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('subscription_plans')->updateOrInsert(
            [
                'slug' => 'monthly',
            ],
            [
                'name' => 'Monthly Plan',
                'price' => 29.99,
                'billing_cycle' => 'month',
                'trial_days' => 7,
                'currency' => 'USD',
                'is_active' => 1,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}