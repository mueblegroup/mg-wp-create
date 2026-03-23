<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            ['name' => 'Starter Alpha', 'slug' => 'starter-alpha', 'zip_path' => 'themes/starter-alpha.zip', 'min_plan_level' => 1],
            ['name' => 'Starter Beta', 'slug' => 'starter-beta', 'zip_path' => 'themes/starter-beta.zip', 'min_plan_level' => 1],
            ['name' => 'Starter Gamma', 'slug' => 'starter-gamma', 'zip_path' => 'themes/starter-gamma.zip', 'min_plan_level' => 1],
            ['name' => 'Business Flow', 'slug' => 'business-flow', 'zip_path' => 'themes/business-flow.zip', 'min_plan_level' => 1],
            ['name' => 'Portfolio One', 'slug' => 'portfolio-one', 'zip_path' => 'themes/portfolio-one.zip', 'min_plan_level' => 1],
            ['name' => 'Studio Wave', 'slug' => 'studio-wave', 'zip_path' => 'themes/studio-wave.zip', 'min_plan_level' => 1],
            ['name' => 'Agency Rise', 'slug' => 'agency-rise', 'zip_path' => 'themes/agency-rise.zip', 'min_plan_level' => 1],
            ['name' => 'Modern Shop', 'slug' => 'modern-shop', 'zip_path' => 'themes/modern-shop.zip', 'min_plan_level' => 1],
            ['name' => 'Clean Service', 'slug' => 'clean-service', 'zip_path' => 'themes/clean-service.zip', 'min_plan_level' => 1],
            ['name' => 'Landing Prime', 'slug' => 'landing-prime', 'zip_path' => 'themes/landing-prime.zip', 'min_plan_level' => 1],

            ['name' => 'Silver Craft', 'slug' => 'silver-craft', 'zip_path' => 'themes/silver-craft.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Studio', 'slug' => 'silver-studio', 'zip_path' => 'themes/silver-studio.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Commerce', 'slug' => 'silver-commerce', 'zip_path' => 'themes/silver-commerce.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Edge', 'slug' => 'silver-edge', 'zip_path' => 'themes/silver-edge.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Space', 'slug' => 'silver-space', 'zip_path' => 'themes/silver-space.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Luxe', 'slug' => 'silver-luxe', 'zip_path' => 'themes/silver-luxe.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Focus', 'slug' => 'silver-focus', 'zip_path' => 'themes/silver-focus.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Grid', 'slug' => 'silver-grid', 'zip_path' => 'themes/silver-grid.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Motion', 'slug' => 'silver-motion', 'zip_path' => 'themes/silver-motion.zip', 'min_plan_level' => 2],
            ['name' => 'Silver Launch', 'slug' => 'silver-launch', 'zip_path' => 'themes/silver-launch.zip', 'min_plan_level' => 2],

            ['name' => 'Gold Elite', 'slug' => 'gold-elite', 'zip_path' => 'themes/gold-elite.zip', 'min_plan_level' => 3],
            ['name' => 'Gold Empire', 'slug' => 'gold-empire', 'zip_path' => 'themes/gold-empire.zip', 'min_plan_level' => 3],
            ['name' => 'Gold Prestige', 'slug' => 'gold-prestige', 'zip_path' => 'themes/gold-prestige.zip', 'min_plan_level' => 3],
            ['name' => 'Gold Signature', 'slug' => 'gold-signature', 'zip_path' => 'themes/gold-signature.zip', 'min_plan_level' => 3],
            ['name' => 'Gold Commerce Pro', 'slug' => 'gold-commerce-pro', 'zip_path' => 'themes/gold-commerce-pro.zip', 'min_plan_level' => 3],
        ];

        foreach ($themes as $theme) {
            Theme::updateOrCreate(
                ['slug' => $theme['slug']],
                [
                    'name' => $theme['name'],
                    'zip_path' => $theme['zip_path'],
                    'preview_image' => null,
                    'min_plan_level' => $theme['min_plan_level'],
                    'description' => null,
                    'is_active' => true,
                ]
            );
        }
    }
}