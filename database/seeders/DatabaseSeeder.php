<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Order: content (menu, hero, features) → blog → settings → roles/permissions + admin user.
     */
    public function run(): void
    {
        $this->call([
            // Content: Navigation & Hero
            MenuItemSeeder::class,
            HeroSectionSeeder::class,
            FeatureSeeder::class,

            // Content: Homepage Sections
            HowItWorksStepSeeder::class,
            WhyChooseSectionSeeder::class,
            TestimonialSectionHeaderSeeder::class,
            TestimonialSeeder::class,
            AppDownloadSectionSeeder::class,
            FaqSectionHeaderSeeder::class,
            FaqCategorySeeder::class,

            // Content: Blog
            BlogSeeder::class,

            // Settings
            SettingSeeder::class,
            SettingsSeeder::class,

            // System: Roles, Types, Categories
            RoleAndPermissionSeeder::class,
            MealTypeSeeder::class,
            PlanCategorySeeder::class,
            ServiceSeeder::class,
            MenuSeeder::class,
            OfferSeeder::class,
            PlanSeeder::class,
        ]);
    }
}
