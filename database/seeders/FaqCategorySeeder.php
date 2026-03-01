<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Seeder;

class FaqCategorySeeder extends Seeder
{
    public function run(): void
    {
        Faq::query()->delete();
        FaqCategory::query()->delete();

        // --- Categories ---
        $general = FaqCategory::create([
            'slug' => 'general',
            'icon' => 'heroicon-o-question-mark-circle',
            'is_active' => true,
            'order_column' => 1,
            'en' => ['name' => 'General'],
            'ar' => ['name' => 'عام'],
        ]);

        $plans = FaqCategory::create([
            'slug' => 'meal-plans',
            'icon' => 'heroicon-o-clipboard-document-list',
            'is_active' => true,
            'order_column' => 2,
            'en' => ['name' => 'Meal Plans'],
            'ar' => ['name' => 'خطط الوجبات'],
        ]);

        $delivery = FaqCategory::create([
            'slug' => 'delivery',
            'icon' => 'heroicon-o-truck',
            'is_active' => true,
            'order_column' => 3,
            'en' => ['name' => 'Delivery'],
            'ar' => ['name' => 'التوصيل'],
        ]);

        $payment = FaqCategory::create([
            'slug' => 'payment',
            'icon' => 'heroicon-o-credit-card',
            'is_active' => true,
            'order_column' => 4,
            'en' => ['name' => 'Payment & Subscription'],
            'ar' => ['name' => 'الدفع والاشتراك'],
        ]);

        // --- FAQs ---
        $faqs = [
            // General
            [
                'faq_category_id' => $general->id,
                'order_column' => 1,
                'en' => [
                    'question' => 'What is Diet Watchers?',
                    'answer' => 'Diet Watchers is a premium healthy meal delivery service in Saudi Arabia. We prepare fresh, nutritionist-designed meals and deliver them to your door daily.',
                ],
                'ar' => [
                    'question' => 'ما هي دايت واتشرز؟',
                    'answer' => 'دايت واتشرز هي خدمة توصيل وجبات صحية متميزة في المملكة العربية السعودية. نقوم بتحضير وجبات طازجة مصممة من قبل أخصائيي تغذية وتوصيلها إلى بابك يوميًا.',
                ],
            ],
            [
                'faq_category_id' => $general->id,
                'order_column' => 2,
                'en' => [
                    'question' => 'Who designs the meal plans?',
                    'answer' => 'Our meal plans are designed by certified nutritionists and dietitians who ensure every meal is balanced, portion-controlled, and aligned with your health goals.',
                ],
                'ar' => [
                    'question' => 'من يصمم خطط الوجبات؟',
                    'answer' => 'يتم تصميم خطط وجباتنا من قبل أخصائيي تغذية معتمدين يضمنون أن كل وجبة متوازنة ومحسوبة الحصص ومتوافقة مع أهدافك الصحية.',
                ],
            ],
            [
                'faq_category_id' => $general->id,
                'order_column' => 3,
                'en' => [
                    'question' => 'Which cities do you serve?',
                    'answer' => 'We currently serve Riyadh, Jeddah, and Dammam. We are continuously expanding to more cities across Saudi Arabia.',
                ],
                'ar' => [
                    'question' => 'ما المدن التي تخدمونها؟',
                    'answer' => 'نخدم حاليًا الرياض وجدة والدمام. نحن نتوسع باستمرار إلى مزيد من المدن في المملكة العربية السعودية.',
                ],
            ],

            // Meal Plans
            [
                'faq_category_id' => $plans->id,
                'order_column' => 1,
                'en' => [
                    'question' => 'Can I customize my meals?',
                    'answer' => 'Yes! You can specify dietary preferences, allergies, and food dislikes. Our system will tailor your daily meals accordingly while maintaining nutritional balance.',
                ],
                'ar' => [
                    'question' => 'هل يمكنني تخصيص وجباتي؟',
                    'answer' => 'نعم! يمكنك تحديد تفضيلاتك الغذائية والحساسيات والأطعمة التي لا تحبها. سيقوم نظامنا بتعديل وجباتك اليومية وفقًا لذلك مع الحفاظ على التوازن الغذائي.',
                ],
            ],
            [
                'faq_category_id' => $plans->id,
                'order_column' => 2,
                'en' => [
                    'question' => 'How many calories are in each plan?',
                    'answer' => 'We offer plans ranging from 1,200 to 3,000 calories per day. You can choose based on your goals — whether it\'s weight loss, maintenance, or muscle gain.',
                ],
                'ar' => [
                    'question' => 'كم عدد السعرات الحرارية في كل خطة؟',
                    'answer' => 'نقدم خططًا تتراوح من 1,200 إلى 3,000 سعرة حرارية يوميًا. يمكنك الاختيار بناءً على أهدافك — سواء كانت خسارة الوزن أو الثبات أو بناء العضلات.',
                ],
            ],
            [
                'faq_category_id' => $plans->id,
                'order_column' => 3,
                'en' => [
                    'question' => 'How many meals per day do I get?',
                    'answer' => 'Depending on your plan, you receive 3 to 5 meals per day including breakfast, snacks, lunch, and dinner. Each meal is freshly prepared.',
                ],
                'ar' => [
                    'question' => 'كم عدد الوجبات التي أحصل عليها يوميًا؟',
                    'answer' => 'حسب خطتك، تحصل على 3 إلى 5 وجبات يوميًا تشمل الإفطار والوجبات الخفيفة والغداء والعشاء. كل وجبة يتم تحضيرها طازجة.',
                ],
            ],

            // Delivery
            [
                'faq_category_id' => $delivery->id,
                'order_column' => 1,
                'en' => [
                    'question' => 'What time are meals delivered?',
                    'answer' => 'Meals are delivered early morning between 6:00 AM and 9:00 AM so they\'re ready for your day. You\'ll receive a notification when your delivery is on the way.',
                ],
                'ar' => [
                    'question' => 'في أي وقت يتم توصيل الوجبات؟',
                    'answer' => 'يتم توصيل الوجبات في الصباح الباكر بين الساعة 6:00 و 9:00 صباحًا لتكون جاهزة ليومك. ستتلقى إشعارًا عندما يكون التوصيل في الطريق.',
                ],
            ],
            [
                'faq_category_id' => $delivery->id,
                'order_column' => 2,
                'en' => [
                    'question' => 'Can I pause or skip delivery days?',
                    'answer' => 'Yes, you can pause your subscription or skip specific days through the app or by contacting our support team at least 48 hours in advance.',
                ],
                'ar' => [
                    'question' => 'هل يمكنني إيقاف أو تخطي أيام التوصيل؟',
                    'answer' => 'نعم، يمكنك إيقاف اشتراكك أو تخطي أيام محددة من خلال التطبيق أو بالتواصل مع فريق الدعم قبل 48 ساعة على الأقل.',
                ],
            ],

            // Payment
            [
                'faq_category_id' => $payment->id,
                'order_column' => 1,
                'en' => [
                    'question' => 'What payment methods do you accept?',
                    'answer' => 'We accept all major credit cards (Visa, Mastercard), mada debit cards, Apple Pay, and bank transfers. All payments are processed securely through Moyasar.',
                ],
                'ar' => [
                    'question' => 'ما طرق الدفع المقبولة؟',
                    'answer' => 'نقبل جميع بطاقات الائتمان الرئيسية (فيزا، ماستركارد)، وبطاقات مدى، و Apple Pay، والتحويلات البنكية. يتم معالجة جميع المدفوعات بشكل آمن عبر Moyasar.',
                ],
            ],
            [
                'faq_category_id' => $payment->id,
                'order_column' => 2,
                'en' => [
                    'question' => 'Can I get a refund?',
                    'answer' => 'Refunds are available for undelivered meals. If you need to cancel, please contact us at least 48 hours before your next delivery. Partial refunds are calculated based on remaining days.',
                ],
                'ar' => [
                    'question' => 'هل يمكنني استرداد المبلغ؟',
                    'answer' => 'الاسترداد متاح للوجبات غير المسلمة. إذا كنت بحاجة للإلغاء، يرجى التواصل معنا قبل 48 ساعة على الأقل من التوصيل التالي. يتم حساب الاسترداد الجزئي بناءً على الأيام المتبقية.',
                ],
            ],
            [
                'faq_category_id' => $payment->id,
                'order_column' => 3,
                'en' => [
                    'question' => 'Do you offer discounts or promo codes?',
                    'answer' => 'Yes! We regularly offer promotional discounts and seasonal offers. Follow us on social media or subscribe to our newsletter to stay updated. You can apply promo codes at checkout.',
                ],
                'ar' => [
                    'question' => 'هل تقدمون خصومات أو أكواد ترويجية؟',
                    'answer' => 'نعم! نقدم بانتظام خصومات ترويجية وعروض موسمية. تابعنا على وسائل التواصل الاجتماعي أو اشترك في نشرتنا لتبقى على اطلاع. يمكنك تطبيق الأكواد الترويجية عند الدفع.',
                ],
            ],
        ];

        foreach ($faqs as $data) {
            Faq::create(array_merge(['is_active' => true], $data));
        }
    }
}
