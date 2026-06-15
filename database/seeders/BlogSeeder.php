<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use App\Services\Blog\BlogAssetImagePicker;
use App\Services\Blog\BlogPostWriter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

/**
 * Seeds blog categories, tags, and sample posts with full EN/AR translations and SEO fields.
 */
class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $this->command?->info('🌱 Seeding blog module...');

        $this->ensureBlogCoverAssets();
        $categories = $this->seedCategories();
        $tags = $this->seedTags();
        $images = (new BlogAssetImagePicker)->pickMany(8);
        $writer = new BlogPostWriter;

        $posts = $this->postDefinitions($images->all());

        foreach ($posts as $index => $postData) {
            $slugEn = $postData['en']['slug'] ?? '';
            if ($slugEn !== '' && BlogPost::query()->whereTranslation('slug', $slugEn, 'en')->exists()) {
                $this->command?->warn("   ⏭ Skipped (exists): {$slugEn}");

                continue;
            }

            $post = $writer->create($postData);
            $this->command?->info('   ✅ '.($index + 1).". {$post->translate('en')->title}");
        }

        $this->command?->info('✅ Blog seeding complete!');
        $this->command?->info("   - {$categories->count()} categories");
        $this->command?->info("   - {$tags->count()} tags");
        $this->command?->info('   - '.BlogPost::query()->count().' posts total');
    }

    private function ensureBlogCoverAssets(): void
    {
        $blogFour = public_path('assets/images/blog-4.png');
        if (! File::exists($blogFour)) {
            $source = public_path('assets/images/avocato-plante.png');
            if (File::exists($source)) {
                File::copy($source, $blogFour);
                $this->command?->info('   📷 Created assets/images/blog-4.png from avocato-plante.png');
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, BlogCategory>
     */
    private function seedCategories()
    {
        $items = [
            [
                'slug' => 'nutrition',
                'order_column' => 1,
                'en' => ['name' => 'Nutrition', 'description' => 'Evidence-based nutrition tips and guides.'],
                'ar' => ['name' => 'التغذية', 'description' => 'نصائح وأدلة غذائية مبنية على أسس علمية.'],
            ],
            [
                'slug' => 'wellness',
                'order_column' => 2,
                'en' => ['name' => 'Wellness', 'description' => 'Holistic health and daily wellness habits.'],
                'ar' => ['name' => 'الصحة والعافية', 'description' => 'الصحة الشاملة وعادات العافية اليومية.'],
            ],
            [
                'slug' => 'meal-prep',
                'order_column' => 3,
                'en' => ['name' => 'Meal Prep', 'description' => 'Plan, cook, and store meals efficiently.'],
                'ar' => ['name' => 'تحضير الوجبات', 'description' => 'خطط واطبخ وخزّن وجباتك بكفاءة.'],
            ],
            [
                'slug' => 'fitness',
                'order_column' => 4,
                'en' => ['name' => 'Fitness', 'description' => 'Training, recovery, and active living.'],
                'ar' => ['name' => 'اللياقة البدنية', 'description' => 'التمرين والتعافي ونمط الحياة النشط.'],
            ],
            [
                'slug' => 'weight-management',
                'order_column' => 5,
                'en' => ['name' => 'Weight Management', 'description' => 'Sustainable strategies for healthy weight goals.'],
                'ar' => ['name' => 'إدارة الوزن', 'description' => 'استراتيجيات مستدامة لأهداف وزن صحية.'],
            ],
        ];

        $created = collect();
        foreach ($items as $item) {
            $category = BlogCategory::firstOrCreate(
                ['slug' => $item['slug']],
                ['is_active' => true, 'order_column' => $item['order_column']]
            );
            foreach (['en', 'ar'] as $locale) {
                $category->translateOrNew($locale)->fill($item[$locale])->save();
            }
            $created->push($category);
        }

        return $created;
    }

    /**
     * @return \Illuminate\Support\Collection<int, BlogTag>
     */
    private function seedTags()
    {
        $items = [
            ['slug' => 'nutrition', 'en' => 'Nutrition', 'ar' => 'التغذية'],
            ['slug' => 'wellness', 'en' => 'Wellness', 'ar' => 'الصحة العامة'],
            ['slug' => 'diet', 'en' => 'Diet', 'ar' => 'النظام الغذائي'],
            ['slug' => 'meal-prep', 'en' => 'Meal Prep', 'ar' => 'تحضير الوجبات'],
            ['slug' => 'fitness', 'en' => 'Fitness', 'ar' => 'اللياقة البدنية'],
            ['slug' => 'healthy-eating', 'en' => 'Healthy Eating', 'ar' => 'أكل صحي'],
            ['slug' => 'weight-loss', 'en' => 'Weight Loss', 'ar' => 'فقدان الوزن'],
            ['slug' => 'recipes', 'en' => 'Recipes', 'ar' => 'وصفات'],
        ];

        $created = collect();
        foreach ($items as $item) {
            $tag = BlogTag::firstOrCreate(['slug' => $item['slug']], ['is_active' => true]);
            $tag->translateOrNew('en')->fill(['name' => $item['en']])->save();
            $tag->translateOrNew('ar')->fill(['name' => $item['ar']])->save();
            $created->push($tag);
        }

        return $created;
    }

    /**
     * @param  list<string>  $images
     * @return list<array<string, mixed>>
     */
    private function postDefinitions(array $images): array
    {
        $authorId = User::query()->value('id');
        $site = 'Diet Watchers';

        $img = static fn (int $i, string $fallback = 'assets/images/blog-1.png'): string => $images[$i] ?? $fallback;

        return [
            [
                'status' => 'published',
                'published_at' => now()->subDays(14),
                'is_featured' => true,
                'cover_image_path' => $img(0),
                'reading_time_minutes' => 6,
                'author_id' => $authorId,
                'category_slug' => 'wellness',
                'tags' => ['nutrition', 'wellness', 'healthy-eating'],
                'en' => [
                    'title' => 'Healthy Lifestyle: 10 Tips for a Better You',
                    'slug' => 'healthy-lifestyle-tips',
                    'excerpt' => 'Discover simple yet effective tips to transform your daily routine and embrace a healthier lifestyle.',
                    'content' => '<p>Leading a healthy lifestyle does not have to be complicated. Small, consistent changes in sleep, hydration, movement, and nutrition compound over time.</p><h2>Start with hydration</h2><p>Drink water throughout the day—especially in Saudi Arabia\'s climate. Aim for steady intake rather than large amounts at once.</p><h2>Build balanced plates</h2><ul><li>Half vegetables</li><li>Quarter lean protein</li><li>Quarter complex carbs</li></ul><p>These ten habits are a practical starting point for sustainable wellness.</p>',
                    'meta_title' => "10 Healthy Lifestyle Tips | {$site}",
                    'meta_description' => 'Simple daily habits for better energy, nutrition, and wellness. Practical tips from Diet Watchers Saudi Arabia.',
                    'meta_keywords' => 'healthy lifestyle, wellness tips, nutrition, Saudi Arabia, Diet Watchers',
                    'og_title' => '10 Tips for a Healthier Lifestyle',
                    'og_description' => 'Practical wellness habits you can start today.',
                ],
                'ar' => [
                    'title' => 'نمط حياة صحي: 10 نصائح لحياة أفضل',
                    'slug' => 'healthy-lifestyle-tips-ar',
                    'excerpt' => 'اكتشف نصائح بسيطة وفعالة لتحويل روتينك اليومي وتبني نمط حياة صحي.',
                    'content' => '<p>قيادة نمط حياة صحي لا يجب أن تكون معقدة. التغييرات الصغيرة والمستمرة في النوم والترطيب والحركة والتغذية تتراكم مع الوقت.</p><h2>ابدأ بالترطيب</h2><p>اشرب الماء على مدار اليوم—خصوصاً في مناخ المملكة. يفضّل التوزيع على كميات كبيرة دفعة واحدة.</p><h2>ابنِ أطباقاً متوازنة</h2><ul><li>نصف الخضار</li><li>ربع بروتين خفيف</li><li>ربع كربوهيدرات معقدة</li></ul><p>هذه العادات العشر نقطة انطلاق عملية للعافية المستدامة.</p>',
                    'meta_title' => "10 نصائح لنمط حياة صحي | {$site}",
                    'meta_description' => 'عادات يومية بسيطة لطاقة أفضل وتغذية وعافية. نصائح عملية من دايت ووتشرز.',
                    'meta_keywords' => 'نمط حياة صحي, عافية, تغذية, السعودية, دايت ووتشرز',
                    'og_title' => '10 نصائح لحياة أكثر صحة',
                    'og_description' => 'عادات عافية عملية يمكنك البدء بها اليوم.',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(11),
                'is_featured' => false,
                'cover_image_path' => $img(1),
                'reading_time_minutes' => 8,
                'author_id' => $authorId,
                'category_slug' => 'nutrition',
                'tags' => ['nutrition', 'diet', 'healthy-eating'],
                'en' => [
                    'title' => 'The Ultimate Nutrition Guide for Beginners',
                    'slug' => 'nutrition-guide-beginners',
                    'excerpt' => 'Everything you need to know about nutrition basics, from macros to micronutrients.',
                    'content' => '<p>Starting your nutrition journey can feel overwhelming. This guide breaks down macronutrients, micronutrients, and portion basics.</p><h2>Macronutrients</h2><p>Protein supports muscle and satiety. Carbohydrates fuel activity. Fats support hormones and absorption.</p><h2>Micronutrients matter</h2><p>Vitamins and minerals from whole foods help immunity, energy, and recovery—prioritize colorful produce.</p>',
                    'meta_title' => 'Beginner Nutrition Guide | Diet Watchers',
                    'meta_description' => 'Learn macros, micros, and balanced eating basics. A clear nutrition guide for beginners in Saudi Arabia.',
                    'meta_keywords' => 'nutrition guide, macros, beginners, healthy eating',
                    'og_title' => 'Nutrition Guide for Beginners',
                    'og_description' => 'Macros, micros, and balanced meals explained simply.',
                ],
                'ar' => [
                    'title' => 'دليل التغذية الشامل للمبتدئين',
                    'slug' => 'nutrition-guide-beginners-ar',
                    'excerpt' => 'كل ما تحتاج معرفته عن أساسيات التغذية، من العناصر الكبرى إلى الصغرى.',
                    'content' => '<p>قد تشعر ببداية رحلتك الغذائية بأنها مربكة. يوضح هذا الدليل العناصر الكبرى والصغرى وأساسيات الحصص.</p><h2>العناصر الكبرى</h2><p>البروتين يدعم العضلات والشبع. الكربوهيدرات تزوّد النشاط. الدهون تدعم الهرمونات والامتصاص.</p><h2>أهمية العناصر الصغرى</h2><p>الفيتامينات والمعادن من الأطعمة الكاملة تدعم المناعة والطاقة والتعافي.</p>',
                    'meta_title' => 'دليل التغذية للمبتدئين | دايت ووتشرز',
                    'meta_description' => 'تعرّف على العناصر الغذائية الكبرى والصغرى وأساسيات الأكل المتوازن.',
                    'meta_keywords' => 'دليل التغذية, عناصر غذائية, مبتدئين',
                    'og_title' => 'دليل التغذية للمبتدئين',
                    'og_description' => 'شرح مبسط للعناصر الغذائية والوجبات المتوازنة.',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(8),
                'is_featured' => false,
                'cover_image_path' => $img(2),
                'reading_time_minutes' => 6,
                'author_id' => $authorId,
                'category_slug' => 'meal-prep',
                'tags' => ['meal-prep', 'diet', 'recipes'],
                'en' => [
                    'title' => 'Weekly Meal Prep: Save Time and Eat Healthy',
                    'slug' => 'meal-prep-weekly-guide',
                    'excerpt' => 'Master the art of meal prepping with our step-by-step weekly guide.',
                    'content' => '<p>Meal prep is a game-changer for busy schedules. Plan proteins, grains, and vegetables once—eat well all week.</p><h2>Sunday setup</h2><p>Batch-cook chicken or fish, roast vegetables, and portion grains into containers.</p><h2>Storage tips</h2><p>Refrigerate 3–4 days of meals; freeze extras. Label dates to reduce waste.</p>',
                    'meta_title' => 'Weekly Meal Prep Guide | Diet Watchers',
                    'meta_description' => 'Step-by-step weekly meal prep to save time and eat healthier. Storage tips and batch cooking ideas.',
                    'meta_keywords' => 'meal prep, weekly planning, healthy meals',
                    'og_title' => 'Weekly Meal Prep Guide',
                    'og_description' => 'Save time with smart batch cooking and storage.',
                ],
                'ar' => [
                    'title' => 'تحضير الوجبات الأسبوعي: وفر الوقت وتناول طعاماً صحياً',
                    'slug' => 'meal-prep-weekly-guide-ar',
                    'excerpt' => 'أتقن فن تحضير الوجبات مع دليلنا الأسبوعي خطوة بخطوة.',
                    'content' => '<p>تحضير الوجبات يغيّر قواعد اللعبة للجداول المزدحمة. خطط للبروتين والحبوب والخضار مرة واحدة—وتناول جيداً طوال الأسبوع.</p><h2>تحضير نهاية الأسبوع</h2><p>اطبخ الدجاج أو السمك بكميات، اشوِ الخضار، وقسّم الحبوب في حاويات.</p><h2>نصائح التخزين</h2><p>برد وجبات 3–4 أيام؛ جمّد الزائد. ضع تاريخاً على الحاويات.</p>',
                    'meta_title' => 'دليل تحضير الوجبات الأسبوعي | دايت ووتشرز',
                    'meta_description' => 'تحضير وجبات أسبوعي خطوة بخطوة لتوفير الوقت وأكل صحي.',
                    'meta_keywords' => 'تحضير وجبات, تخطيط أسبوعي, وجبات صحية',
                    'og_title' => 'دليل التحضير الأسبوعي',
                    'og_description' => 'وفر الوقت بالطبخ المجمّع والتخزين الذكي.',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(5),
                'is_featured' => true,
                'cover_image_path' => $img(3),
                'reading_time_minutes' => 7,
                'author_id' => $authorId,
                'category_slug' => 'fitness',
                'tags' => ['fitness', 'nutrition', 'wellness'],
                'en' => [
                    'title' => 'Balancing Fitness and Nutrition: The Perfect Duo',
                    'slug' => 'fitness-nutrition-balance',
                    'excerpt' => 'Learn how to combine exercise and proper nutrition for optimal results.',
                    'content' => '<p>Fitness and nutrition work together. Fuel workouts with adequate carbs and protein; recover with sleep and hydration.</p><h2>Pre-workout</h2><p>Light carbs 60–90 minutes before training support energy without heaviness.</p><h2>Post-workout</h2><p>Protein plus carbs within two hours aids muscle repair and glycogen replenishment.</p>',
                    'meta_title' => 'Fitness & Nutrition Balance | Diet Watchers',
                    'meta_description' => 'Combine training and smart eating for better performance and recovery.',
                    'meta_keywords' => 'fitness nutrition, workout meals, recovery',
                    'og_title' => 'Fitness + Nutrition',
                    'og_description' => 'How to fuel and recover for better results.',
                ],
                'ar' => [
                    'title' => 'التوازن بين اللياقة والتغذية: الثنائي المثالي',
                    'slug' => 'fitness-nutrition-balance-ar',
                    'excerpt' => 'تعلم كيفية الجمع بين التمارين والتغذية السليمة للحصول على نتائج مثالية.',
                    'content' => '<p>اللياقة والتغذية يعملان معاً. زوّد تمارينك بالكربوهيدرات والبروتين الكافي؛ وتعافَ بالنوم والترطيب.</p><h2>قبل التمرين</h2><p>كربوهيدرات خفيفة قبل 60–90 دقيقة تدعم الطاقة دون ثقل.</p><h2>بعد التمرين</h2><p>بروتين مع كربوهيدرات خلال ساعتين يساعد على الإصلاح وتعبئة الجليكوجين.</p>',
                    'meta_title' => 'التوازن بين اللياقة والتغذية | دايت ووتشرز',
                    'meta_description' => 'اجمع بين التمرين والأكل الذكي لأداء وتعافٍ أفضل.',
                    'meta_keywords' => 'لياقة, تغذية رياضية, تعافي',
                    'og_title' => 'اللياقة + التغذية',
                    'og_description' => 'كيف تزوّد جسمك وتتعافى لنتائج أفضل.',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(4),
                'is_featured' => false,
                'cover_image_path' => $img(4),
                'reading_time_minutes' => 5,
                'author_id' => $authorId,
                'category_slug' => 'nutrition',
                'tags' => ['healthy-eating', 'nutrition', 'recipes'],
                'en' => [
                    'title' => '5 High-Protein Breakfast Ideas for Busy Mornings',
                    'slug' => 'high-protein-breakfast-ideas',
                    'excerpt' => 'Quick, satisfying breakfasts that keep you full until lunch.',
                    'content' => '<p>Protein at breakfast reduces mid-morning cravings and supports muscle maintenance.</p><h2>Ideas</h2><ul><li>Greek yogurt with berries and nuts</li><li>Egg white omelet with vegetables</li><li>Overnight oats with protein powder</li><li>Smoked salmon wrap</li><li>Cottage cheese bowl</li></ul>',
                    'meta_title' => 'High-Protein Breakfast Ideas | Diet Watchers',
                    'meta_description' => 'Five quick high-protein breakfasts for busy mornings in Saudi Arabia.',
                    'meta_keywords' => 'protein breakfast, healthy breakfast, meal ideas',
                    'og_title' => '5 Protein Breakfast Ideas',
                    'og_description' => 'Quick breakfasts that keep you satisfied.',
                ],
                'ar' => [
                    'title' => '5 أفكار فطور غني بالبروتين للصباح المزدحم',
                    'slug' => 'high-protein-breakfast-ideas-ar',
                    'excerpt' => 'فطور سريع ومشبع يبقيك ممتلئاً حتى الغداء.',
                    'content' => '<p>البروتين في الفطور يقلل الرغبة في الوجبات الخفيفة ويدعم العضلات.</p><h2>أفكار</h2><ul><li>زبادي يوناني مع توت ومكسرات</li><li>أومليت بياض بيض مع خضار</li><li>شوفان ليلي مع بروتين</li><li>لفافة سلمون مدخن</li><li>وعاء جبن قريش</li></ul>',
                    'meta_title' => 'أفكار فطور غني بالبروتين | دايت ووتشرز',
                    'meta_description' => 'خمسة أفطار سريعة غنية بالبروتين للصباح المزدحم.',
                    'meta_keywords' => 'فطور بروتين, فطور صحي, وجبات',
                    'og_title' => '5 أفكار فطور بروتين',
                    'og_description' => 'فطور سريع يبقيك مشبعاً.',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(3),
                'is_featured' => false,
                'cover_image_path' => $img(5),
                'reading_time_minutes' => 7,
                'author_id' => $authorId,
                'category_slug' => 'weight-management',
                'tags' => ['weight-loss', 'diet', 'nutrition'],
                'en' => [
                    'title' => 'Sustainable Weight Management: What Actually Works',
                    'slug' => 'sustainable-weight-management',
                    'excerpt' => 'Skip fad diets—focus on habits that last.',
                    'content' => '<p>Sustainable weight management is about consistency, not extremes. Prioritize protein, fiber, steps, and sleep.</p><h2>Calorie awareness</h2><p>You do not need perfection—track patterns for a week to spot liquid calories and large portions.</p><h2>Meal delivery support</h2><p>Pre-portioned meals remove guesswork and help you stay on plan during busy weeks.</p>',
                    'meta_title' => 'Sustainable Weight Management | Diet Watchers',
                    'meta_description' => 'Habits that support healthy weight goals without extreme diets.',
                    'meta_keywords' => 'weight management, healthy habits, diet plan',
                    'og_title' => 'Sustainable Weight Management',
                    'og_description' => 'Habits that work long-term.',
                ],
                'ar' => [
                    'title' => 'إدارة الوزن المستدامة: ما الذي ينجح فعلاً',
                    'slug' => 'sustainable-weight-management-ar',
                    'excerpt' => 'تجاوز الحميات العشوائية—ركّز على العادات الدائمة.',
                    'content' => '<p>إدارة الوزن المستدامة تعتمد على الاستمرارية لا التطرف. أولوية للبروتين والألياف والخطوات والنوم.</p><h2>الوعي بالسعرات</h2><p>لا تحتاج الكمال—راقب أنماطك لأسبوع لاكتشاف السعرات السائلة والحصص الكبيرة.</p><h2>دعم توصيل الوجبات</h2><p>الوجبات الجاهزة بالحصص تزيل التخمين وتساعدك على الالتزام.</p>',
                    'meta_title' => 'إدارة الوزن المستدامة | دايت ووتشرز',
                    'meta_description' => 'عادات تدعم أهداف وزن صحية دون حميات متطرفة.',
                    'meta_keywords' => 'إدارة الوزن, عادات صحية, نظام غذائي',
                    'og_title' => 'إدارة الوزن المستدامة',
                    'og_description' => 'عادات تعمل على المدى الطويل.',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'is_featured' => false,
                'cover_image_path' => $img(6),
                'reading_time_minutes' => 6,
                'author_id' => $authorId,
                'category_slug' => 'wellness',
                'tags' => ['wellness', 'healthy-eating', 'meal-prep'],
                'en' => [
                    'title' => 'How to Eat Healthy When Dining Out in Riyadh',
                    'slug' => 'healthy-eating-dining-out-riyadh',
                    'excerpt' => 'Smart choices at restaurants without sacrificing enjoyment.',
                    'content' => '<p>Dining out is part of social life. You can enjoy restaurants while staying aligned with your goals.</p><h2>Menu strategy</h2><p>Look for grilled proteins, salads with dressing on the side, and share large plates.</p><h2>Portion control</h2><p>Box half your meal before eating or choose two appetizers instead of a heavy main.</p>',
                    'meta_title' => 'Healthy Dining Out in Riyadh | Diet Watchers',
                    'meta_description' => 'Practical tips for eating healthy at restaurants in Riyadh and Saudi Arabia.',
                    'meta_keywords' => 'healthy dining, Riyadh restaurants, eating out',
                    'og_title' => 'Healthy Dining Out Tips',
                    'og_description' => 'Enjoy restaurants while staying on track.',
                ],
                'ar' => [
                    'title' => 'كيف تأكل صحياً عند تناول الطعام خارج المنزل في الرياض',
                    'slug' => 'healthy-eating-dining-out-riyadh-ar',
                    'excerpt' => 'خيارات ذكية في المطاعم دون التضحية بالمتعة.',
                    'content' => '<p>تناول الطعام خارج المنزل جزء من الحياة الاجتماعية. يمكنك الاستمتاع بالمطاعم مع الالتزام بأهدافك.</p><h2>استراتيجية القائمة</h2><p>ابحث عن البروتينات المشوية والسلطات مع الصلصة جانباً وشارك الأطباق الكبيرة.</p><h2>التحكم بالحصص</h2><p>ضع نصف وجبتك للتغليف قبل الأكل أو اختر مقبلتين بدل طبق رئيسي ثقيل.</p>',
                    'meta_title' => 'أكل صحي خارج المنزل في الرياض | دايت ووتشرز',
                    'meta_description' => 'نصائح عملية للأكل الصحي في مطاعم الرياض والمملكة.',
                    'meta_keywords' => 'أكل صحي, مطاعم الرياض, خارج المنزل',
                    'og_title' => 'نصائح الأكل الصحي خارج المنزل',
                    'og_description' => 'استمتع بالمطاعم مع الالتزام بخطتك.',
                ],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDay(),
                'is_featured' => true,
                'cover_image_path' => $img(7),
                'reading_time_minutes' => 5,
                'author_id' => $authorId,
                'category_slug' => 'meal-prep',
                'tags' => ['recipes', 'meal-prep', 'healthy-eating'],
                'en' => [
                    'title' => 'Mediterranean Bowl Meal Prep for the Week',
                    'slug' => 'mediterranean-bowl-meal-prep',
                    'excerpt' => 'Colorful, balanced bowls you can prep in under an hour.',
                    'content' => '<p>Mediterranean bowls combine fiber, healthy fats, and lean protein—ideal for meal prep.</p><h2>Base ingredients</h2><p>Quinoa or brown rice, chickpeas, cucumber, tomato, olives, feta, grilled chicken, lemon-tahini dressing.</p><h2>Assembly</h2><p>Store dressing separately; combine when ready to eat for maximum freshness.</p>',
                    'meta_title' => 'Mediterranean Meal Prep Bowls | Diet Watchers',
                    'meta_description' => 'Prep colorful Mediterranean bowls for a week of healthy lunches.',
                    'meta_keywords' => 'mediterranean bowl, meal prep recipe, healthy lunch',
                    'og_title' => 'Mediterranean Bowl Meal Prep',
                    'og_description' => 'Balanced bowls ready in under an hour.',
                ],
                'ar' => [
                    'title' => 'تحضير وعاء متوسطي لوجبات الأسبوع',
                    'slug' => 'mediterranean-bowl-meal-prep-ar',
                    'excerpt' => 'أوعية ملونة ومتوازنة يمكن تحضيرها في أقل من ساعة.',
                    'content' => '<p>الأوعية المتوسطية تجمع الألياف والدهون الصحية والبروتين الخفيف—مثالية للتحضير المسبق.</p><h2>المكونات الأساسية</h2><p>كينوا أو أرز بني، حمص، خيار، طماطم، زيتون، جبن فيتا، دجاج مشوي، تتبيلة ليمون وطحينة.</p><h2>التجميع</h2><p>خزّن التتبيلة منفصلة؛ امزج عند الأكل للحصول على نضارة أفضل.</p>',
                    'meta_title' => 'تحضير أوعية متوسطية | دايت ووتشرز',
                    'meta_description' => 'حضّر أوعية متوسطية ملونة لأسبوع من الغداء الصحي.',
                    'meta_keywords' => 'وعاء متوسطي, تحضير وجبات, غداء صحي',
                    'og_title' => 'تحضير الوعاء المتوسطي',
                    'og_description' => 'أوعية متوازنة جاهزة في أقل من ساعة.',
                ],
            ],
        ];
    }
}
