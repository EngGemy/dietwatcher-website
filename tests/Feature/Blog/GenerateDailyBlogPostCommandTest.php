<?php

declare(strict_types=1);

namespace Tests\Feature\Blog;

use App\Models\BlogPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class GenerateDailyBlogPostCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_persists_a_bilingual_blog_post(): void
    {
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.thinking_budget', 0);

        Http::fake([
            '*' => Http::response($this->geminiSuccessResponse(), 200),
        ]);

        $this->artisan('blog:generate-daily --force -vvv')
            ->assertExitCode(0);

        $post = BlogPost::query()->first();
        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertNotEmpty($post->translate('en')?->title);
        $this->assertNotEmpty($post->translate('ar')?->title);
        $this->assertNotEmpty($post->translate('en')?->slug);
        $this->assertNotEmpty($post->translate('ar')?->slug);
    }

    public function test_it_retries_after_429_then_succeeds(): void
    {
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.thinking_budget', 0);

        Http::fakeSequence()
            ->push([
                'error' => [
                    'code' => 429,
                    'message' => 'Rate limit exceeded',
                    'details' => [
                        ['retryDelay' => '0s'],
                    ],
                ],
            ], 429)
            ->push($this->geminiSuccessResponse(), 200);

        $this->artisan('blog:generate-daily --force -vvv')
            ->assertExitCode(0);

        $this->assertSame(1, BlogPost::query()->count());
    }

    public function test_it_logs_clear_reason_and_returns_null_on_empty_parts(): void
    {
        config()->set('services.gemini.key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.thinking_budget', 0);

        Log::spy();

        Http::fake([
            '*' => Http::response([
                'candidates' => [
                    [
                        'finishReason' => 'STOP',
                        'content' => [
                            'parts' => [],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('blog:generate-daily --force -vvv')
            ->assertExitCode(1);

        $this->assertSame(0, BlogPost::query()->count());
        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $message, array $context): bool {
                return str_contains($message, 'empty parts')
                    && isset($context['model']);
            })
            ->atLeast()
            ->once();
    }

    /**
     * @return array<string, mixed>
     */
    private function geminiSuccessResponse(): array
    {
        $json = json_encode([
            'category_slug' => 'nutrition',
            'tags' => ['nutrition', 'healthy-eating'],
            'reading_time_minutes' => 6,
            'is_featured' => false,
            'en' => [
                'title' => 'Healthy Plate Basics for Everyday Eating',
                'slug' => 'healthy-plate-basics',
                'excerpt' => 'Build simple, balanced meals with practical plate portions.',
                'content' => '<p>Balanced eating starts with practical plate planning.</p><p>Use vegetables, lean protein, and smart carbs.</p><h2>Simple formula</h2><ul><li>Half vegetables</li><li>Quarter protein</li><li>Quarter complex carbs</li></ul>',
                'meta_title' => 'Healthy Plate Basics | Diet Watchers',
                'meta_description' => 'Simple balanced plate method for healthier daily meals.',
                'meta_keywords' => 'healthy plate, nutrition, balanced meals',
                'og_title' => 'Healthy Plate Basics',
                'og_description' => 'A practical approach to balanced daily eating.',
            ],
            'ar' => [
                'title' => 'أساسيات الطبق الصحي للأكل اليومي',
                'slug' => 'healthy-plate-basics-ar',
                'excerpt' => 'ابنِ وجبات متوازنة بطريقة عملية وسهلة.',
                'content' => '<p>الأكل المتوازن يبدأ بتخطيط بسيط للطبق.</p><p>استخدم الخضار والبروتين الخفيف والكربوهيدرات الذكية.</p><h2>معادلة سهلة</h2><ul><li>نصف خضار</li><li>ربع بروتين</li><li>ربع كربوهيدرات معقدة</li></ul>',
                'meta_title' => 'أساسيات الطبق الصحي | دايت ووتشرز',
                'meta_description' => 'طريقة بسيطة لبناء طبق متوازن يومياً.',
                'meta_keywords' => 'طبق صحي, تغذية, وجبات متوازنة',
                'og_title' => 'أساسيات الطبق الصحي',
                'og_description' => 'أسلوب عملي للأكل المتوازن يومياً.',
            ],
        ], JSON_UNESCAPED_UNICODE);

        return [
            'candidates' => [
                [
                    'finishReason' => 'STOP',
                    'content' => [
                        'parts' => [
                            ['text' => $json],
                        ],
                    ],
                ],
            ],
        ];
    }
}

