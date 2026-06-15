<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Blog\BlogContentGeneratorService;
use Illuminate\Console\Command;

class GenerateDailyBlogPostCommand extends Command
{
    protected $signature = 'blog:generate-daily
                            {--topic= : Optional topic hint for Gemini}
                            {--force : Generate even if a post was already created today}';

    protected $description = 'Generate and publish one bilingual blog post using Gemini AI';

    public function handle(BlogContentGeneratorService $generator): int
    {
        if (! $generator->isConfigured()) {
            $this->error('GEMINI_API_KEY is not set. Add it to .env to enable AI blog generation.');

            return self::FAILURE;
        }

        if (! $this->option('force') && $generator->alreadyGeneratedToday()) {
            $this->warn('A blog post was already created today. Use --force to generate another.');

            return self::SUCCESS;
        }

        $this->info('Generating blog post with Gemini...');

        $post = $generator->generateDaily(
            topic: $this->option('topic') ? (string) $this->option('topic') : null,
            force: (bool) $this->option('force'),
        );

        if ($post === null) {
            $this->error('Failed to generate blog post. Check logs for details.');

            return self::FAILURE;
        }

        $this->info("Published: {$post->translate('en')->title}");
        $this->line("  EN slug: {$post->translate('en')->slug}");
        $this->line("  AR slug: {$post->translate('ar')->slug}");
        $this->line("  Cover:   {$post->cover_image_path}");

        return self::SUCCESS;
    }
}
