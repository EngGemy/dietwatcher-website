<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        Testimonial::query()->delete();

        $testimonials = [
            [
                'rating' => 5,
                'is_active' => true,
                'order_column' => 1,
                'en' => [
                    'author_name' => 'Sarah Al-Rashidi',
                    'author_title' => 'Lost 12kg in 3 months',
                    'content' => 'Diet Watchers completely changed my relationship with food. The meals are delicious and I never feel like I\'m on a diet. The daily delivery is so convenient — I finally have time for myself!',
                ],
                'ar' => [
                    'author_name' => 'سارة الرشيدي',
                    'author_title' => 'خسرت 12 كجم في 3 أشهر',
                    'content' => 'دايت واتشرز غيّرت علاقتي بالطعام تمامًا. الوجبات لذيذة ولا أشعر أبدًا أنني أتبع حمية. التوصيل اليومي مريح جدًا — أخيرًا لدي وقت لنفسي!',
                ],
            ],
            [
                'rating' => 5,
                'is_active' => true,
                'order_column' => 2,
                'en' => [
                    'author_name' => 'Mohammed Al-Dosari',
                    'author_title' => 'Fitness Enthusiast',
                    'content' => 'As someone who works out daily, I need precise macro tracking. Diet Watchers provides exactly the right calories and protein I need. The variety keeps me excited about every meal.',
                ],
                'ar' => [
                    'author_name' => 'محمد الدوسري',
                    'author_title' => 'محب للياقة البدنية',
                    'content' => 'كشخص يتمرن يوميًا، أحتاج لتتبع دقيق للعناصر الغذائية. دايت واتشرز توفر بالضبط السعرات والبروتين الذي أحتاجه. التنوع يجعلني متحمسًا لكل وجبة.',
                ],
            ],
            [
                'rating' => 5,
                'is_active' => true,
                'order_column' => 3,
                'en' => [
                    'author_name' => 'Noura Al-Qahtani',
                    'author_title' => 'Busy Working Mom',
                    'content' => 'Between work and kids, I had no time to cook healthy meals. Diet Watchers solved that problem entirely. My whole family eats better now, and I\'ve lost 8kg without even trying!',
                ],
                'ar' => [
                    'author_name' => 'نورة القحطاني',
                    'author_title' => 'أم عاملة مشغولة',
                    'content' => 'بين العمل والأطفال، لم يكن لدي وقت لطهي وجبات صحية. دايت واتشرز حلت هذه المشكلة تمامًا. عائلتي كلها تأكل بشكل أفضل الآن، وخسرت 8 كجم بدون حتى أن أحاول!',
                ],
            ],
            [
                'rating' => 4,
                'is_active' => true,
                'order_column' => 4,
                'en' => [
                    'author_name' => 'Abdullah Al-Shehri',
                    'author_title' => 'Corporate Professional',
                    'content' => 'I used to skip meals or eat fast food at the office. Now my healthy lunch arrives on time every day. I have more energy, better focus, and my colleagues keep asking what my secret is.',
                ],
                'ar' => [
                    'author_name' => 'عبدالله الشهري',
                    'author_title' => 'موظف في القطاع الخاص',
                    'content' => 'كنت أتجاوز الوجبات أو آكل وجبات سريعة في المكتب. الآن غدائي الصحي يصل في الوقت كل يوم. لدي طاقة أكثر وتركيز أفضل وزملائي يسألونني عن سري.',
                ],
            ],
            [
                'rating' => 5,
                'is_active' => true,
                'order_column' => 5,
                'en' => [
                    'author_name' => 'Fatima Al-Harbi',
                    'author_title' => 'Health Transformation',
                    'content' => 'My doctor recommended I change my diet after some health concerns. Diet Watchers made it easy with their specialized plans. My blood sugar is under control and I feel amazing.',
                ],
                'ar' => [
                    'author_name' => 'فاطمة الحربي',
                    'author_title' => 'تحول صحي',
                    'content' => 'نصحني طبيبي بتغيير نظامي الغذائي بعد بعض المخاوف الصحية. دايت واتشرز جعلت الأمر سهلاً مع خططهم المتخصصة. سكر الدم تحت السيطرة وأشعر بشعور رائع.',
                ],
            ],
            [
                'rating' => 5,
                'is_active' => true,
                'order_column' => 6,
                'en' => [
                    'author_name' => 'Khalid Al-Mutairi',
                    'author_title' => 'Lost 20kg in 6 months',
                    'content' => 'I tried every diet out there and nothing worked long-term. Diet Watchers is different because it\'s sustainable. The food is genuinely good and the portions keep me satisfied. Best investment in my health.',
                ],
                'ar' => [
                    'author_name' => 'خالد المطيري',
                    'author_title' => 'خسرت 20 كجم في 6 أشهر',
                    'content' => 'جربت كل الحميات ولم ينجح شيء على المدى الطويل. دايت واتشرز مختلفة لأنها مستدامة. الطعام لذيذ فعلاً والحصص تبقيني راضيًا. أفضل استثمار في صحتي.',
                ],
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::create($data);
        }
    }
}
