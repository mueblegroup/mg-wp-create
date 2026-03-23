<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach (config('saas.plans') as $name => $plan) {
            Plan::updateOrCreate(
                ['name' => $name],
                [
                    'label' => $plan['label'],
                    'price' => $plan['price'],
                    'currency' => $plan['currency'],
                    'allows_custom_domain' => $plan['allows_custom_domain'],
                    'max_themes' => $plan['max_themes'],
                    'resource_profile' => $plan['resource_profile'],
                    'sort_order' => $plan['sort_order'],
                    'is_active' => true,
                ]
            );
        }
    }
}