<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HowItWorksStep;
use Illuminate\Database\Seeder;

class HowItWorksStepSeeder extends Seeder
{
    public function run(): void
    {
        HowItWorksStep::query()->delete();

        $steps = [
            [
                'image' => 'how-it-works/step-1.svg',
                'title_en' => 'Choose Your Plan',
                'title_ar' => 'اختر خطتك',
                'description_en' => 'Select a meal plan based on calories, lifestyle, or fitness goals.',
                'description_ar' => 'اختر خطة وجبات بناءً على السعرات الحرارية أو نمط الحياة أو أهداف اللياقة.',
                'order_column' => 1,
                'is_active' => true,
            ],
            [
                'image' => 'how-it-works/step-2.svg',
                'title_en' => 'Swap to Your Favorite Meals',
                'title_ar' => 'بدّل إلى وجباتك المفضلة',
                'description_en' => 'Change meals anytime and enjoy dishes that suit your taste, mood, and lifestyle.',
                'description_ar' => 'بدّل الوجبات في أي وقت واستمتع بأطباق تناسب ذوقك ومزاجك ونمط حياتك.',
                'order_column' => 2,
                'is_active' => true,
            ],
            [
                'image' => 'how-it-works/step-3.svg',
                'title_en' => 'Enjoy Your Meals!',
                'title_ar' => 'استمتع بوجباتك!',
                'description_en' => 'Your meals are ready - fresh, nutritious, and made to enjoy.',
                'description_ar' => 'وجباتك جاهزة - طازجة ومغذية ومصنوعة للاستمتاع.',
                'order_column' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($steps as $step) {
            HowItWorksStep::create($step);
        }
    }
}
