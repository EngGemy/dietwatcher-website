<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\Settings\Setting;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BlogContentGeneratorService
{
    public function __construct(
        private readonly GeminiService $gemini,
        private readonly BlogPostWriter $writer,
        private readonly BlogAssetImagePicker $imagePicker,
    ) {}

    public function isConfigured(): bool
    {
        return $this->gemini->isConfigured();
    }

    public function alreadyGeneratedToday(): bool
    {
        return BlogPost::query()
            ->whereDate('created_at', today())
            ->where('status', 'published')
            ->exists();
    }

    /**
     * Generate and publish one bilingual blog post via Gemini.
     */
    public function generateDaily(?string $topic = null, bool $force = false): ?BlogPost
    {
        if (! $this->isConfigured()) {
            Log::info('BlogContentGeneratorService: GEMINI_API_KEY not set');

            return null;
        }

        if (! $force && $this->alreadyGeneratedToday()) {
            Log::info('BlogContentGeneratorService: post already created today');

            return null;
        }

        $payload = $this->fetchPayloadFromGemini($topic);
        if ($payload === null) {
            return null;
        }

        $coverImage = $this->imagePicker->pick($payload['cover_image_path'] ?? null);
        $payload['cover_image_path'] = $coverImage;
        $payload['status'] = 'published';
        $payload['published_at'] = now();
        $payload['is_featured'] = (bool) ($payload['is_featured'] ?? false);
        $payload['seo_indexable'] = true;
        $payload['seo_follow'] = true;

        foreach (['en', 'ar'] as $locale) {
            if (isset($payload[$locale]) && is_array($payload[$locale])) {
                $payload[$locale]['og_image_path'] = $coverImage;
            }
        }

        $this->ensureTagsExist($payload['tags'] ?? []);
        $this->ensureCategoryExists($payload['category_slug'] ?? 'nutrition');

        return $this->writer->create($payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchPayloadFromGemini(?string $topic): ?array
    {
        $siteName = Setting::getValue('site_name', config('app.name', 'Diet Watchers'));
        $categories = BlogCategory::query()->where('is_active', true)->pluck('slug')->all();
        $tags = BlogTag::query()->where('is_active', true)->pluck('slug')->all();

        if ($categories === []) {
            $categories = ['nutrition', 'wellness', 'meal-prep', 'fitness', 'weight-management'];
        }
        if ($tags === []) {
            $tags = ['nutrition', 'wellness', 'diet', 'meal-prep', 'fitness', 'healthy-eating', 'weight-loss', 'recipes'];
        }

        $topicLine = $topic
            ? "Write about this specific topic: {$topic}."
            : 'Choose a fresh, timely nutrition or healthy-lifestyle topic relevant to Saudi Arabia.';

        $system = <<<PROMPT
You are the SEO content writer for "{$siteName}", a healthy meal delivery brand in Saudi Arabia.
Write ONE complete blog article with bilingual EN and AR content.
Return ONLY valid JSON (no markdown fences) matching this schema:
{
  "category_slug": "one of: {$this->joinList($categories)}",
  "tags": ["2-4 slugs from: {$this->joinList($tags)}"],
  "reading_time_minutes": 5-10,
  "is_featured": false,
  "en": {
    "title": "string",
    "slug": "english-kebab-slug",
    "excerpt": "max 160 chars",
    "content": "HTML with <p>, <h2>, <ul><li> — at least 4 paragraphs",
    "meta_title": "max 60 chars, include brand when natural",
    "meta_description": "max 155 chars",
    "meta_keywords": "comma,separated,keywords",
    "og_title": "string",
    "og_description": "max 200 chars"
  },
  "ar": {
    "title": "string",
    "slug": "arabic-topic-slug-ar",
    "excerpt": "max 160 chars Arabic",
    "content": "Arabic HTML, same structure as EN",
    "meta_title": "max 60 chars Arabic",
    "meta_description": "max 155 chars Arabic",
    "meta_keywords": "Arabic keywords comma separated",
    "og_title": "string Arabic",
    "og_description": "max 200 chars Arabic"
  }
}
Rules:
- Professional, helpful tone; no medical claims or guaranteed weight-loss promises.
- Content must be original and practical (tips, guides, meal ideas).
- Arabic must be natural MSA, not machine-literal translation.
- Slugs: lowercase, hyphens only, unique style per locale.
PROMPT;

        $raw = $this->gemini->generate($system, [
            ['role' => 'user', 'content' => $topicLine],
        ], jsonMode: true, temperature: 0.55);

        if ($raw === null) {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (! is_array($decoded) || empty($decoded['en']['title']) || empty($decoded['ar']['title'])) {
            Log::warning('BlogContentGeneratorService: invalid JSON from Gemini', [
                'raw' => Str::limit($raw, 500),
            ]);

            return null;
        }

        return $decoded;
    }

    /**
     * @param  list<string>  $slugs
     */
    private function ensureTagsExist(array $slugs): void
    {
        foreach ($slugs as $slug) {
            $slug = Str::slug($slug);
            if ($slug === '') {
                continue;
            }

            $tag = BlogTag::firstOrCreate(['slug' => $slug], ['is_active' => true]);
            $name = Str::headline(str_replace('-', ' ', $slug));
            $tag->translateOrNew('en')->fill(['name' => $name])->save();
            $tag->translateOrNew('ar')->fill(['name' => $name])->save();
        }
    }

    private function ensureCategoryExists(string $slug): void
    {
        $slug = Str::slug($slug);
        if ($slug === '') {
            return;
        }

        if (BlogCategory::query()->where('slug', $slug)->exists()) {
            return;
        }

        $category = BlogCategory::create([
            'slug' => $slug,
            'is_active' => true,
            'order_column' => (int) BlogCategory::query()->max('order_column') + 1,
        ]);

        $name = Str::headline(str_replace('-', ' ', $slug));
        $category->translateOrNew('en')->fill([
            'name' => $name,
            'description' => "Articles about {$name}.",
        ])->save();
        $category->translateOrNew('ar')->fill([
            'name' => $name,
            'description' => "مقالات عن {$name}.",
        ])->save();
    }

    /**
     * @param  list<string>  $items
     */
    private function joinList(array $items): string
    {
        return implode(', ', $items);
    }
}
