<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add CMS fields to blog_posts table
        Schema::table('blog_posts', function (Blueprint $table) {
            // Make slug nullable since we use per-locale slugs in translations
            $table->string('slug')->nullable()->change();
            
            // Status field (replace is_published)
            $table->string('status')->default('draft')->after('author_id')
                ->comment('draft|published|scheduled|archived');
            
            // Scheduling
            $table->timestamp('scheduled_at')->nullable()->after('published_at');
            
            // Featured flag
            $table->boolean('is_featured')->default(false)->after('slug');
            
            // Cover image (rename from featured_image)
            $table->renameColumn('featured_image', 'cover_image_path');
            
            // Reading time
            $table->unsignedSmallInteger('reading_time_minutes')->nullable()->after('cover_image_path');
            
            // Comments control
            $table->boolean('allow_comments')->default(true)->after('reading_time_minutes');
            
            // Counters
            $table->unsignedBigInteger('views_count')->default(0)->after('allow_comments');
            $table->unsignedBigInteger('likes_count')->default(0)->after('views_count');
            
            // SEO control
            $table->boolean('seo_indexable')->default(true)->after('robots');
            $table->boolean('seo_follow')->default(true)->after('seo_indexable');
            
            // Indexes
            $table->index('status');
            $table->index('scheduled_at');
            $table->index('is_featured');
        });

        // Add slug and og_image_path to translations
        Schema::table('blog_post_translations', function (Blueprint $table) {
            $table->string('slug')->after('locale');
            $table->string('og_image_path')->nullable()->after('og_description');
            
            // Unique slug per locale
            $table->unique(['locale', 'slug']);
            
            // Make content nullable for drafts
            $table->longText('content')->nullable()->change();
        });

        // Create post_likes table
        if (!Schema::hasTable('post_likes')) {
            Schema::create('post_likes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('ip_address', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at');
                
                // Prevent duplicate likes
                $table->unique(['blog_post_id', 'user_id'], 'post_user_unique');
                $table->index(['blog_post_id', 'ip_address']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table) {
            $table->dropColumn([
                'status',
                'scheduled_at',
                'is_featured',
                'reading_time_minutes',
                'allow_comments',
                'views_count',
                'likes_count',
                'seo_indexable',
                'seo_follow',
            ]);
            
            $table->renameColumn('cover_image_path', 'featured_image');
        });

        Schema::table('blog_post_translations', function (Blueprint $table) {
            $table->dropUnique(['locale', 'slug']);
            $table->dropColumn(['slug', 'og_image_path']);
        });

        Schema::dropIfExists('post_likes');
    }
};
