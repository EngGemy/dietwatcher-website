<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Content\Feature;
use App\Models\Content\FeatureTranslation;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Placeholder image URL for feature screens/mockups (visible in admin).
     */
    private const PLACEHOLDER_IMAGE = 'https://placehold.co/400x700/10b981/ffffff?text=Feature';

    public function run(): void
    {
        $features = [
            [
                'order' => 1,
                'image' => 'features/fresh-meals.png',
                'en_title' => 'Fresh Daily Meals',
                'en_description' => 'Every meal is freshly prepared by professional chefs using premium ingredients. No frozen food, no preservatives — just real, wholesome nutrition delivered to your door.',
                'ar_title' => 'وجبات طازجة يوميًا',
                'ar_description' => 'كل وجبة يتم تحضيرها طازجة من قبل طهاة محترفين باستخدام مكونات عالية الجودة. بدون طعام مجمد، بدون مواد حافظة — فقط تغذية حقيقية وصحية تصل إلى بابك.',
            ],
            [
                'order' => 2,
                'image' => 'features/expert-plans.png',
                'en_title' => 'Expert Nutrition Plans',
                'en_description' => 'Our certified nutritionists design every plan with precise calorie counts, balanced macros, and variety that keeps you excited about healthy eating week after week.',
                'ar_title' => 'خطط تغذية متخصصة',
                'ar_description' => 'يصمم أخصائيو التغذية المعتمدون لدينا كل خطة بسعرات حرارية دقيقة وعناصر غذائية متوازنة وتنوع يبقيك متحمسًا للأكل الصحي أسبوعًا بعد أسبوع.',
            ],
            [
                'order' => 3,
                'image' => 'features/flexible-options.png',
                'en_title' => 'Flexible Options',
                'en_description' => 'Swap meals, pause your subscription, or change your plan anytime. We adapt to your lifestyle — not the other way around. Choose from multiple calorie levels and dietary preferences.',
                'ar_title' => 'خيارات مرنة',
                'ar_description' => 'بدّل الوجبات أو أوقف اشتراكك أو غيّر خطتك في أي وقت. نحن نتكيف مع نمط حياتك — وليس العكس. اختر من مستويات سعرات حرارية متعددة وتفضيلات غذائية مختلفة.',
            ],
            [
                'order' => 4,
                'image' => 'features/convenient-delivery.png',
                'en_title' => 'Convenient Daily Delivery',
                'en_description' => 'Your meals arrive fresh every morning before you start your day. Track your delivery in real-time and enjoy hassle-free healthy eating without any cooking or cleanup.',
                'ar_title' => 'توصيل يومي مريح',
                'ar_description' => 'تصل وجباتك طازجة كل صباح قبل أن تبدأ يومك. تتبع التوصيل في الوقت الفعلي واستمتع بأكل صحي بدون عناء الطبخ أو التنظيف.',
            ],
        ];

        foreach ($features as $f) {
            $feature = Feature::create([
                'order' => $f['order'],
                'image' => $f['image'],
                'is_active' => true,
            ]);
            FeatureTranslation::create([
                'feature_id' => $feature->id,
                'locale' => 'en',
                'title' => $f['en_title'],
                'description' => $f['en_description'],
            ]);
            FeatureTranslation::create([
                'feature_id' => $feature->id,
                'locale' => 'ar',
                'title' => $f['ar_title'],
                'description' => $f['ar_description'],
            ]);
        }
    }
}
