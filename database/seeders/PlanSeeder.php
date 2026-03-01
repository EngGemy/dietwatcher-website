<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MealType;
use App\Models\Menu;
use App\Models\Offer;
use App\Models\Plan;
use App\Models\PlanCalorie;
use App\Models\PlanCalorieMacro;
use App\Models\PlanCategory;
use App\Models\PlanDuration;
use App\Models\PlanImage;
use App\Models\PlanMenu;
use App\Models\PlanTranslation;
use App\Models\Service;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'is_active' => true,
                'show_in_app' => true,
                'order_column' => 1,
                'hero_image' => null,
                'translations' => [
                    'en' => [
                        'name' => 'High Protein Plan',
                        'subtitle' => 'Build muscle and stay energized',
                        'description' => '<p>Our High Protein Plan is designed for those looking to build muscle mass and maintain high energy levels throughout the day. Each meal is carefully crafted to provide optimal protein intake while maintaining a balanced nutritional profile.</p>',
                        'ingredients' => '<p>Premium lean meats, fish, eggs, Greek yogurt, quinoa, brown rice, fresh vegetables, and healthy fats from nuts and avocados.</p>',
                        'benefits' => '<ul><li>Supports muscle growth and recovery</li><li>Maintains high energy levels</li><li>Promotes satiety and reduces cravings</li><li>Supports metabolic health</li></ul>',
                    ],
                    'ar' => [
                        'name' => 'خطة عالية البروتين',
                        'subtitle' => 'بناء العضلات والبقاء نشيطًا',
                        'description' => '<p>تم تصميم خطتنا عالية البروتين لأولئك الذين يتطلعون إلى بناء كتلة العضلات والحفاظ على مستويات عالية من الطاقة طوال اليوم.</p>',
                        'ingredients' => '<p>لحوم خالية من الدهون، أسماك، بيض، زبادي يوناني، كينوا، أرز بني، خضروات طازجة، ودهون صحية من المكسرات والأفوكادو.</p>',
                        'benefits' => '<ul><li>يدعم نمو العضلات والتعافي</li><li>يحافظ على مستويات الطاقة العالية</li><li>يعزز الشبع ويقلل الرغبة الشديدة</li><li>يدعم صحة التمثيل الغذائي</li></ul>',
                    ],
                ],
                'categories' => ['high-protein', 'bulking'],
                'meal_types' => ['breakfast', 'lunch', 'dinner', 'snack'],
                'calories' => [
                    [
                        'min_amount' => 1800,
                        'max_amount' => 2000,
                        'is_default' => true,
                        'is_active' => true,
                        'order_column' => 1,
                        'macros' => [
                            'calories' => 1900,
                            'protein_g' => 150.00,
                            'carbs_g' => 180.00,
                            'fat_g' => 60.00,
                        ],
                    ],
                    [
                        'min_amount' => 2200,
                        'max_amount' => 2500,
                        'is_default' => false,
                        'is_active' => true,
                        'order_column' => 2,
                        'macros' => [
                            'calories' => 2350,
                            'protein_g' => 180.00,
                            'carbs_g' => 220.00,
                            'fat_g' => 75.00,
                        ],
                    ],
                ],
                'durations' => [
                    [
                        'days' => 30,
                        'price' => 2100.00,
                        'delivery_price' => 150.00,
                        'service_id' => 1,
                        'service_price' => 50.00,
                        'start_date' => now()->addDays(3),
                        'currency' => 'SAR',
                        'is_default' => true,
                        'is_active' => true,
                        'order_column' => 1,
                        'offers' => [1],
                    ],
                    [
                        'days' => 60,
                        'price' => 3900.00,
                        'delivery_price' => 250.00,
                        'service_id' => 1,
                        'service_price' => 80.00,
                        'start_date' => now()->addDays(3),
                        'currency' => 'SAR',
                        'is_default' => false,
                        'is_active' => true,
                        'order_column' => 2,
                        'offers' => [1, 3],
                    ],
                ],
                'menus' => [1, 2],
            ],
            [
                'is_active' => true,
                'show_in_app' => true,
                'order_column' => 2,
                'hero_image' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Weight Loss Plan',
                        'subtitle' => 'Achieve your ideal weight healthily',
                        'description' => '<p>Our Weight Loss Plan is scientifically designed to help you lose weight in a healthy and sustainable way. With controlled portions and balanced nutrition, you\'ll achieve your goals without feeling deprived.</p>',
                        'ingredients' => '<p>Lean proteins, whole grains, plenty of vegetables, fruits, and healthy fats. All meals are portion-controlled and calorie-counted.</p>',
                        'benefits' => '<ul><li>Sustainable weight loss</li><li>Improved metabolism</li><li>Increased energy levels</li><li>Better overall health</li></ul>',
                    ],
                    'ar' => [
                        'name' => 'خطة فقدان الوزن',
                        'subtitle' => 'حقق وزنك المثالي بشكل صحي',
                        'description' => '<p>تم تصميم خطة فقدان الوزن لدينا علميًا لمساعدتك على فقدان الوزن بطريقة صحية ومستدامة.</p>',
                        'ingredients' => '<p>بروتينات خالية من الدهون، حبوب كاملة، الكثير من الخضروات، الفواكه، والدهون الصحية.</p>',
                        'benefits' => '<ul><li>فقدان الوزن المستدام</li><li>تحسين التمثيل الغذائي</li><li>زيادة مستويات الطاقة</li><li>صحة عامة أفضل</li></ul>',
                    ],
                ],
                'categories' => ['weight-loss', 'balanced'],
                'meal_types' => ['breakfast', 'lunch', 'dinner'],
                'calories' => [
                    [
                        'min_amount' => 1200,
                        'max_amount' => 1450,
                        'is_default' => true,
                        'is_active' => true,
                        'order_column' => 1,
                        'macros' => [
                            'calories' => 1325,
                            'protein_g' => 100.00,
                            'carbs_g' => 130.00,
                            'fat_g' => 40.00,
                        ],
                    ],
                    [
                        'min_amount' => 1500,
                        'max_amount' => 1700,
                        'is_default' => false,
                        'is_active' => true,
                        'order_column' => 2,
                        'macros' => [
                            'calories' => 1600,
                            'protein_g' => 120.00,
                            'carbs_g' => 150.00,
                            'fat_g' => 50.00,
                        ],
                    ],
                ],
                'durations' => [
                    [
                        'days' => 30,
                        'price' => 1800.00,
                        'delivery_price' => 150.00,
                        'service_id' => 1,
                        'service_price' => 50.00,
                        'start_date' => now()->addDays(2),
                        'currency' => 'SAR',
                        'is_default' => true,
                        'is_active' => true,
                        'order_column' => 1,
                        'offers' => [],
                    ],
                ],
                'menus' => [1, 5],
            ],
            [
                'is_active' => true,
                'show_in_app' => true,
                'order_column' => 3,
                'hero_image' => null,
                'translations' => [
                    'en' => [
                        'name' => 'Balanced Nutrition Plan',
                        'subtitle' => 'Perfect balance for everyday wellness',
                        'description' => '<p>The Balanced Nutrition Plan provides the perfect equilibrium of macronutrients for maintaining optimal health and energy throughout your day.</p>',
                        'ingredients' => '<p>A variety of lean proteins, complex carbohydrates, healthy fats, colorful vegetables, and seasonal fruits.</p>',
                        'benefits' => '<ul><li>Maintains healthy weight</li><li>Supports overall wellness</li><li>Provides sustained energy</li><li>Promotes digestive health</li></ul>',
                    ],
                    'ar' => [
                        'name' => 'خطة التغذية المتوازنة',
                        'subtitle' => 'التوازن المثالي للصحة اليومية',
                        'description' => '<p>توفر خطة التغذية المتوازنة التوازن المثالي للمغذيات الكبيرة للحفاظ على الصحة المثلى والطاقة طوال يومك.</p>',
                        'ingredients' => '<p>مجموعة متنوعة من البروتينات الخالية من الدهون، الكربوهيدرات المعقدة، الدهون الصحية، الخضروات الملونة، والفواكه الموسمية.</p>',
                        'benefits' => '<ul><li>يحافظ على الوزن الصحي</li><li>يدعم الصحة العامة</li><li>يوفر طاقة مستدامة</li><li>يعزز صحة الجهاز الهضمي</li></ul>',
                    ],
                ],
                'categories' => ['balanced'],
                'meal_types' => ['breakfast', 'lunch', 'dinner', 'snack'],
                'calories' => [
                    [
                        'min_amount' => 1600,
                        'max_amount' => 1800,
                        'is_default' => true,
                        'is_active' => true,
                        'order_column' => 1,
                        'macros' => [
                            'calories' => 1700,
                            'protein_g' => 120.00,
                            'carbs_g' => 170.00,
                            'fat_g' => 55.00,
                        ],
                    ],
                ],
                'durations' => [
                    [
                        'days' => 30,
                        'price' => 1950.00,
                        'delivery_price' => 150.00,
                        'service_id' => 1,
                        'service_price' => 50.00,
                        'start_date' => now()->addDays(5),
                        'currency' => 'SAR',
                        'is_default' => true,
                        'is_active' => true,
                        'order_column' => 1,
                        'offers' => [1],
                    ],
                ],
                'menus' => [1, 4],
            ],
        ];

        foreach ($plans as $planData) {
            // Extract nested data
            $translations = $planData['translations'];
            $categories = $planData['categories'];
            $mealTypes = $planData['meal_types'];
            $calories = $planData['calories'];
            $durations = $planData['durations'];
            $menus = $planData['menus'];

            unset(
                $planData['translations'],
                $planData['categories'],
                $planData['meal_types'],
                $planData['calories'],
                $planData['durations'],
                $planData['menus']
            );

            // Create plan
            $plan = Plan::create($planData);

            // Create translations
            foreach ($translations as $locale => $translation) {
                PlanTranslation::create([
                    'plan_id' => $plan->id,
                    'locale' => $locale,
                    'name' => $translation['name'],
                    'subtitle' => $translation['subtitle'],
                    'description' => $translation['description'],
                    'ingredients' => $translation['ingredients'],
                    'benefits' => $translation['benefits'],
                ]);
            }

            // Attach categories
            $categoryIds = PlanCategory::whereIn('slug', $categories)->pluck('id');
            $plan->categories()->attach($categoryIds);

            // Attach meal types
            $mealTypeIds = MealType::whereIn('slug', $mealTypes)->pluck('id');
            $plan->mealTypes()->attach($mealTypeIds);

            // Create calories with macros
            foreach ($calories as $calorieData) {
                $macrosData = $calorieData['macros'];
                unset($calorieData['macros']);

                $calorie = PlanCalorie::create([
                    'plan_id' => $plan->id,
                    ...$calorieData,
                ]);

                PlanCalorieMacro::create([
                    'plan_calorie_id' => $calorie->id,
                    ...$macrosData,
                ]);
            }

            // Create durations
            foreach ($durations as $durationData) {
                $offerIds = $durationData['offers'] ?? [];
                unset($durationData['offers']);

                $duration = PlanDuration::create([
                    'plan_id' => $plan->id,
                    ...$durationData,
                ]);

                if (!empty($offerIds)) {
                    $duration->offers()->attach($offerIds);
                }
            }

            // Create plan menus
            foreach ($menus as $menuId) {
                PlanMenu::create([
                    'plan_id' => $plan->id,
                    'menu_id' => $menuId,
                    'is_active' => true,
                    'order_column' => 0,
                ]);
            }
        }

        $this->command->info('Plans seeded successfully with complete data!');
    }
}
