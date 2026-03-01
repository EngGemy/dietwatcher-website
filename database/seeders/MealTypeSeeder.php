<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MealType;
use App\Models\MealTypeTranslation;
use Illuminate\Database\Seeder;

class MealTypeSeeder extends Seeder
{
    public function run(): void
    {
        $mealTypes = [
            [
                'slug' => 'breakfast',
                'order_column' => 1,
                'translations' => [
                    'en' => ['name' => 'Breakfast'],
                    'ar' => ['name' => 'فطور'],
                ],
            ],
            [
                'slug' => 'lunch',
                'order_column' => 2,
                'translations' => [
                    'en' => ['name' => 'Lunch'],
                    'ar' => ['name' => 'غداء'],
                ],
            ],
            [
                'slug' => 'dinner',
                'order_column' => 3,
                'translations' => [
                    'en' => ['name' => 'Dinner'],
                    'ar' => ['name' => 'عشاء'],
                ],
            ],
            [
                'slug' => 'snack',
                'order_column' => 4,
                'translations' => [
                    'en' => ['name' => 'Snack'],
                    'ar' => ['name' => 'وجبة خفيفة'],
                ],
            ],
        ];

        foreach ($mealTypes as $mealTypeData) {
            $translations = $mealTypeData['translations'];
            unset($mealTypeData['translations']);

            $mealType = MealType::create($mealTypeData);

            foreach ($translations as $locale => $translation) {
                MealTypeTranslation::create([
                    'meal_type_id' => $mealType->id,
                    'locale' => $locale,
                    'name' => $translation['name'],
                ]);
            }
        }

        $this->command->info('Meal types seeded successfully!');
    }
}
