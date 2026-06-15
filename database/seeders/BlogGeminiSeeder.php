<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Blog\BlogContentGeneratorService;
use Illuminate\Database\Seeder;

/**
 * Generates one AI blog post via Gemini (requires GEMINI_API_KEY).
 *
 * Run: php artisan db:seed --class=BlogGeminiSeeder
 * Or:  php artisan blog:generate-daily
 */
class BlogGeminiSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(BlogContentGeneratorService::class);

        if (! $generator->isConfigured()) {
            $this->command?->warn('⚠️  GEMINI_API_KEY not set — skipping AI blog generation.');
            $this->command?->info('   Set GEMINI_API_KEY in .env then run: php artisan blog:generate-daily');

            return;
        }

        $this->command?->info('🤖 Generating blog post with Gemini...');

        $post = $generator->generateDaily(force: true);

        if ($post === null) {
            $this->command?->error('❌ Failed to generate blog post. Check storage/logs/laravel.log');

            return;
        }

        $this->command?->info("✅ Published: {$post->translate('en')->title}");
        $this->command?->info("   Cover: {$post->cover_image_path}");
        $this->command?->info("   Category: ".($post->category?->translate('en')->name ?? '—'));
        $this->command?->info('   Tags: '.$post->tags->pluck('slug')->join(', '));
    }
}
