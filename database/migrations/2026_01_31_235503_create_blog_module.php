<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Blog Categories
        if (!Schema::hasTable('blog_categories')) {
            Schema::create('blog_categories', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('order_column')->default(0);
                $table->timestamps();

                $table->index(['is_active', 'order_column']);
            });
        }

        if (!Schema::hasTable('blog_category_translations')) {
            Schema::create('blog_category_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_category_id')->constrained('blog_categories')->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('name');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->unique(['blog_category_id', 'locale']);
            });
        }

        // Blog Tags
        if (!Schema::hasTable('blog_tags')) {
            Schema::create('blog_tags', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('blog_tag_translations')) {
            Schema::create('blog_tag_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('name');
                $table->timestamps();

                $table->unique(['blog_tag_id', 'locale']);
            });
        }

        // Blog Posts
        if (!Schema::hasTable('blog_posts')) {
            Schema::create('blog_posts', function (Blueprint $table) {
                $table->id();
                $table->string('slug')->unique();
                $table->foreignId('blog_category_id')->nullable()->constrained('blog_categories')->nullOnDelete();
                $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('featured_image')->nullable();
                $table->string('canonical_url')->nullable();
                $table->string('robots')->default('index,follow');
                $table->string('og_image')->nullable();
                $table->boolean('is_published')->default(false);
                $table->timestamp('published_at')->nullable();
                $table->unsignedInteger('order_column')->default(0);
                $table->timestamps();

                $table->index(['is_published', 'published_at']);
                $table->index('blog_category_id');
                $table->index('author_id');
                $table->index('order_column');
            });
        }

        if (!Schema::hasTable('blog_post_translations')) {
            Schema::create('blog_post_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->string('locale')->index();
                $table->string('title');
                $table->text('excerpt')->nullable();
                $table->longText('content');
                $table->string('meta_title')->nullable();
                $table->text('meta_description')->nullable();
                $table->string('meta_keywords')->nullable();
                $table->string('og_title')->nullable();
                $table->text('og_description')->nullable();
                $table->timestamps();

                $table->unique(['blog_post_id', 'locale']);
            });
        }

        // Pivot: Blog Post <-> Blog Tag
        if (!Schema::hasTable('blog_post_blog_tag')) {
            Schema::create('blog_post_blog_tag', function (Blueprint $table) {
                $table->foreignId('blog_post_id')->constrained('blog_posts')->cascadeOnDelete();
                $table->foreignId('blog_tag_id')->constrained('blog_tags')->cascadeOnDelete();
                $table->primary(['blog_post_id', 'blog_tag_id']);
                $table->index('blog_tag_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_blog_tag');
        Schema::dropIfExists('blog_post_translations');
        Schema::dropIfExists('blog_posts');
        Schema::dropIfExists('blog_tag_translations');
        Schema::dropIfExists('blog_tags');
        Schema::dropIfExists('blog_category_translations');
        Schema::dropIfExists('blog_categories');
    }
};
