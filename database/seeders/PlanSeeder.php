<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price' => 499,
                'currency' => 'INR',
                'interval' => 'monthly',
                'post_limit' => 100,
                'facebook_enabled' => true,
                'instagram_enabled' => true,
                'google_business_enabled' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'price' => 1499,
                'currency' => 'INR',
                'interval' => 'monthly',
                'post_limit' => 500,
                'facebook_enabled' => true,
                'instagram_enabled' => true,
                'google_business_enabled' => true,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Scale',
                'slug' => 'scale',
                'price' => 3999,
                'currency' => 'INR',
                'interval' => 'monthly',
                'post_limit' => 2000,
                'facebook_enabled' => true,
                'instagram_enabled' => true,
                'google_business_enabled' => true,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
