<?php

declare(strict_types=1);

namespace App\Services\Blog;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Support\Str;

class BlogPostWriter
{
    public function __construct(
        private readonly BlogAssetImagePicker $imagePicker = new BlogAssetImagePicker,
    ) {}

    /**
     * @param  array{
     *     status?: string,
     *     published_at?: \DateTimeInterface|string|null,
     *     scheduled_at?: \DateTimeInterface|string|null,
     *     is_featured?: bool,
     *     cover_image_path?: string|null,
     *     reading_time_minutes?: int,
     *     author_id?: int|null,
     *     blog_category_id?: int|null,
     *     category_slug?: string|null,
     *     allow_comments?: bool,
     *     seo_indexable?: bool,
     *     seo_follow?: bool,
     *     canonical_url?: string|null,
     *     robots?: string,
     *     en: array<string, mixed>,
     *     ar: array<string, mixed>,
     *     tags?: list<string>
     * }  $payload
     */
    public function create(array $payload): BlogPost
    {
        $categoryId = $payload['blog_category_id'] ?? null;
        if ($categoryId === null && ! empty($payload['category_slug'])) {
            $categoryId = BlogCategory::query()
                ->where('slug', $payload['category_slug'])
                ->value('id');
        }

        $coverImage = $payload['cover_image_path'] ?? $this->imagePicker->pick();

        $post = BlogPost::create([
            'blog_category_id' => $categoryId,
            'author_id' => $payload['author_id'] ?? User::query()->value('id'),
            'status' => $payload['status'] ?? 'published',
            'published_at' => $payload['published_at'] ?? now(),
            'scheduled_at' => $payload['scheduled_at'] ?? null,
            'is_featured' => (bool) ($payload['is_featured'] ?? false),
            'cover_image_path' => $coverImage,
            'reading_time_minutes' => (int) ($payload['reading_time_minutes'] ?? 5),
            'allow_comments' => (bool) ($payload['allow_comments'] ?? true),
            'seo_indexable' => (bool) ($payload['seo_indexable'] ?? true),
            'seo_follow' => (bool) ($payload['seo_follow'] ?? true),
            'canonical_url' => $payload['canonical_url'] ?? null,
            'robots' => $payload['robots'] ?? 'index,follow',
        ]);

        foreach (['en', 'ar'] as $locale) {
            $translation = $this->normalizeTranslation($payload[$locale] ?? [], $locale, $coverImage);
            $post->translateOrNew($locale)->fill($translation)->save();
        }

        $tagSlugs = $payload['tags'] ?? [];
        if ($tagSlugs !== []) {
            $tagIds = BlogTag::query()->whereIn('slug', $tagSlugs)->pluck('id');
            $post->tags()->sync($tagIds);
        }

        return $post->fresh(['tags', 'category']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeTranslation(array $data, string $locale, string $coverImage): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = trim((string) ($data['slug'] ?? ''));

        if ($slug === '' && $title !== '') {
            $slug = Str::slug($title);
            if ($locale === 'ar' && ! str_ends_with($slug, '-ar')) {
                $slug .= '-ar';
            }
        }

        $metaTitle = trim((string) ($data['meta_title'] ?? $title));
        $excerpt = trim((string) ($data['excerpt'] ?? ''));

        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content' => (string) ($data['content'] ?? ''),
            'meta_title' => Str::limit($metaTitle, 60, ''),
            'meta_description' => Str::limit((string) ($data['meta_description'] ?? $excerpt), 160, ''),
            'meta_keywords' => (string) ($data['meta_keywords'] ?? ''),
            'og_title' => Str::limit((string) ($data['og_title'] ?? $title), 70, ''),
            'og_description' => Str::limit((string) ($data['og_description'] ?? $excerpt), 200, ''),
            'og_image_path' => (string) ($data['og_image_path'] ?? $coverImage),
        ];
    }
}
