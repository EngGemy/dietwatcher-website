<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlanCategory;
use App\Models\PlanCategoryTranslation;
use Illuminate\Database\Seeder;

class PlanCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'all',
                'is_active' => true,
                'order_column' => 0,
                'translations' => [
                    'en' => ['name' => 'All'],
                    'ar' => ['name' => 'الكل'],
                ],
            ],
            [
                'slug' => 'high-protein',
                'is_active' => true,
                'order_column' => 1,
                'translations' => [
                    'en' => ['name' => 'High Protein'],
                    'ar' => ['name' => 'عالي البروتين'],
                ],
            ],
            [
                'slug' => 'vegetarian',
                'is_active' => true,
                'order_column' => 2,
                'translations' => [
                    'en' => ['name' => 'Vegetarian'],
                    'ar' => ['name' => 'نباتي'],
                ],
            ],
            [
                'slug' => 'balanced',
                'is_active' => true,
                'order_column' => 3,
                'translations' => [
                    'en' => ['name' => 'Balanced'],
                    'ar' => ['name' => 'متوازن'],
                ],
            ],
            [
                'slug' => 'weight-loss',
                'is_active' => true,
                'order_column' => 4,
                'translations' => [
                    'en' => ['name' => 'Weight Loss'],
                    'ar' => ['name' => 'فقدان الوزن'],
                ],
            ],
            [
                'slug' => 'drying',
                'is_active' => true,
                'order_column' => 5,
                'translations' => [
                    'en' => ['name' => 'Drying'],
                    'ar' => ['name' => 'تنشيف'],
                ],
            ],
            [
                'slug' => 'bulking',
                'is_active' => true,
                'order_column' => 6,
                'translations' => [
                    'en' => ['name' => 'Bulking'],
                    'ar' => ['name' => 'تضخيم'],
                ],
            ],
        ];

        foreach ($categories as $categoryData) {
            $translations = $categoryData['translations'];
            unset($categoryData['translations']);

            $category = PlanCategory::create($categoryData);

            foreach ($translations as $locale => $translation) {
                PlanCategoryTranslation::create([
                    'plan_category_id' => $category->id,
                    'locale' => $locale,
                    'name' => $translation['name'],
                ]);
            }
        }

        $this->command->info('Plan categories seeded successfully!');
    }
}
