<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Blog Seeder
 * 
 * Seeds sample blog posts with EN/AR translations matching the landing page design.
 */
class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('🌱 Seeding blog posts...');

        // Get first user as default author (nullable)
        $author = User::first();

        // Create tags first
        $tags = $this->createTags();

        // Create 4 sample blog posts
        $posts = [
            [
                'status' => 'published',
                'published_at' => now()->subDays(10),
                'is_featured' => true,
                'cover_image_path' => 'assets/images/blog-1.png',
                'reading_time_minutes' => 5,
                'en' => [
                    'title' => 'Healthy Lifestyle: 10 Tips for a Better You',
                    'slug' => 'healthy-lifestyle-tips',
                    'excerpt' => 'Discover simple yet effective tips to transform your daily routine and embrace a healthier lifestyle.',
                    'content' => '<p>Leading a healthy lifestyle doesn\'t have to be complicated. Start with these 10 simple tips that can make a big difference in your overall well-being.</p><p>From better nutrition choices to regular exercise and mindfulness practices, we cover everything you need to know to start your journey toward better health.</p>',
                ],
                'ar' => [
                    'title' => 'نمط حياة صحي: 10 نصائح لحياة أفضل',
                    'slug' => 'healthy-lifestyle-tips-ar',
                    'excerpt' => 'اكتشف نصائح بسيطة وفعالة لتحويل روتينك اليومي وتبني نمط حياة صحي.',
                    'content' => '<p>قيادة نمط حياة صحي لا يجب أن يكون معقداً. ابدأ بهذه النصائح البسيطة العشرة التي يمكن أن تحدث فرقاً كبيراً في صحتك العامة.</p><p>من خيارات التغذية الأفضل إلى ممارسة الرياضة بانتظام وممارسات اليقظة الذهنية، نغطي كل ما تحتاج لمعرفته لبدء رحلتك نحو صحة أفضل.</p>',
                ],
                'tags' => ['nutrition', 'wellness'],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(7),
                'is_featured' => false,
                'cover_image_path' => 'assets/images/blog-2.png',
                'reading_time_minutes' => 8,
                'en' => [
                    'title' => 'The Ultimate Nutrition Guide for Beginners',
                    'slug' => 'nutrition-guide-beginners',
                    'excerpt' => 'Everything you need to know about nutrition basics, from macros to micronutrients.',
                    'content' => '<p>Starting your nutrition journey can feel overwhelming. This comprehensive guide breaks down the essentials of nutrition science into easy-to-understand concepts.</p><p>Learn about macronutrients, micronutrients, portion control, and how to build balanced meals that support your health goals.</p>',
                ],
                'ar' => [
                    'title' => 'دليل التغذية الشامل للمبتدئين',
                    'slug' => 'nutrition-guide-beginners-ar',
                    'excerpt' => 'كل ما تحتاج معرفته عن أساسيات التغذية، من العناصر الكبرى إلى الصغرى.',
                    'content' => '<p>يمكن أن تشعر ببداية رحلتك الغذائية بأنها مربكة. يقسم هذا الدليل الشامل أساسيات علم التغذية إلى مفاهيم سهلة الفهم.</p><p>تعلم عن العناصر الغذائية الكبرى والصغرى، والتحكم في الحصص، وكيفية بناء وجبات متوازنة تدعم أهدافك الصحية.</p>',
                ],
                'tags' => ['nutrition', 'diet'],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(4),
                'is_featured' => false,
                'cover_image_path' => 'assets/images/blog-3.png',
                'reading_time_minutes' => 6,
                'en' => [
                    'title' => 'Weekly Meal Prep: Save Time and Eat Healthy',
                    'slug' => 'meal-prep-weekly-guide',
                    'excerpt' => 'Master the art of meal prepping with our step-by-step weekly guide.',
                    'content' => '<p>Meal prep is a game-changer for busy individuals who want to maintain a healthy diet. Learn how to plan, prepare, and store meals for the entire week.</p><p>We share practical tips, storage solutions, and delicious recipes that will make meal prep a breeze.</p>',
                ],
                'ar' => [
                    'title' => 'تحضير الوجبات الأسبوعي: وفر الوقت وتناول طعاماً صحياً',
                    'slug' => 'meal-prep-weekly-guide-ar',
                    'excerpt' => 'أتقن فن تحضير الوجبات مع دليلنا الأسبوعي خطوة بخطوة.',
                    'content' => '<p>تحضير الوجبات يغير قواعد اللعبة للأشخاص المشغولين الذين يريدون الحفاظ على نظام غذائي صحي. تعلم كيفية التخطيط والتحضير والتخزين للوجبات لمدة أسبوع كامل.</p><p>نشارك نصائح عملية وحلول تخزين ووصفات لذيذة ستجعل تحضير الوجبات سهلاً.</p>',
                ],
                'tags' => ['meal-prep', 'diet'],
            ],
            [
                'status' => 'published',
                'published_at' => now()->subDays(2),
                'is_featured' => true,
                'cover_image_path' => 'assets/images/blog-4.png',
                'reading_time_minutes' => 7,
                'en' => [
                    'title' => 'Balancing Fitness and Nutrition: The Perfect Duo',
                    'slug' => 'fitness-nutrition-balance',
                    'excerpt' => 'Learn how to combine exercise and proper nutrition for optimal results.',
                    'content' => '<p>Fitness and nutrition go hand in hand. Discover how to optimize your workout performance and recovery through strategic nutrition timing and food choices.</p><p>Whether you\'re building muscle, losing fat, or improving endurance, this guide covers nutrition strategies for every fitness goal.</p>',
                ],
                'ar' => [
                    'title' => 'التوازن بين اللياقة والتغذية: الثنائي المثالي',
                    'slug' => 'fitness-nutrition-balance-ar',
                    'excerpt' => 'تعلم كيفية الجمع بين التمارين والتغذية السليمة للحصول على نتائج مثالية.',
                    'content' => '<p>اللياقة البدنية والتغذية يسيران جنباً إلى جنب. اكتشف كيفية تحسين أداء التمرين والتعافي من خلال توقيت التغذية الاستراتيجي واختيارات الطعام.</p><p>سواء كنت تبني العضلات أو تفقد الدهون أو تحسن التحمل، يغطي هذا الدليل استراتيجيات التغذية لكل هدف لياقة.</p>',
                ],
                'tags' => ['fitness', 'nutrition'],
            ],
        ];

        foreach ($posts as $postData) {
            $tagSlugs = $postData['tags'];
            unset($postData['tags']);

            // Separate base data from translations
            $baseData = [
                'status' => $postData['status'],
                'published_at' => $postData['published_at'],
                'is_featured' => $postData['is_featured'],
                'cover_image_path' => $postData['cover_image_path'],
                'reading_time_minutes' => $postData['reading_time_minutes'],
                'author_id' => $author?->id,
                'allow_comments' => true,
                'seo_indexable' => true,
                'seo_follow' => true,
            ];

            $translations = [
                'en' => $postData['en'],
                'ar' => $postData['ar'],
            ];

            // Create post with translations
            $post = BlogPost::create($baseData);

            // Add translations
            foreach ($translations as $locale => $translation) {
                $post->translateOrNew($locale)->fill($translation)->save();
            }

            // Attach tags
            $postTags = BlogTag::whereIn('slug', $tagSlugs)->pluck('id');
            $post->tags()->attach($postTags);

            $this->command->info("   ✅ Created post: {$post->title} ({$post->slug})");
        }

        $this->command->info('✅ Blog seeding complete!');
        $this->command->info("   - 4 posts created (2 featured)");
        $this->command->info("   - {$tags->count()} tags created");
        $this->command->info("   - All posts have EN + AR translations");
    }

    /**
     * Create sample tags with translations
     */
    protected function createTags()
    {
        $tags = [
            [
                'slug' => 'nutrition',
                'en' => ['name' => 'Nutrition'],
                'ar' => ['name' => 'التغذية'],
            ],
            [
                'slug' => 'wellness',
                'en' => ['name' => 'Wellness'],
                'ar' => ['name' => 'الصحة العامة'],
            ],
            [
                'slug' => 'diet',
                'en' => ['name' => 'Diet'],
                'ar' => ['name' => 'النظام الغذائي'],
            ],
            [
                'slug' => 'meal-prep',
                'en' => ['name' => 'Meal Prep'],
                'ar' => ['name' => 'تحضير الوجبات'],
            ],
            [
                'slug' => 'fitness',
                'en' => ['name' => 'Fitness'],
                'ar' => ['name' => 'اللياقة البدنية'],
            ],
        ];

        $created = collect();

        foreach ($tags as $tagData) {
            $tag = BlogTag::firstOrCreate(
                ['slug' => $tagData['slug']],
                ['is_active' => true]
            );
            
            foreach (['en', 'ar'] as $locale) {
                $tag->translateOrNew($locale)->fill($tagData[$locale])->save();
            }

            $created->push($tag);
        }

        return $created;
    }
}
