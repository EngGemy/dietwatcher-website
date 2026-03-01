<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MealPlansSeeder extends Seeder
{
    /**
     * Run the meal plans module seeders in the correct order.
     */
    public function run(): void
    {
        $this->command->info('Starting Meal Plans Module Seeding...');

        $this->call([
            MealTypeSeeder::class,
            PlanCategorySeeder::class,
            ServiceSeeder::class,
            MenuSeeder::class,
            OfferSeeder::class,
            PlanSeeder::class,
        ]);

        $this->command->info('✅ Meal Plans Module seeded successfully!');
    }
}
