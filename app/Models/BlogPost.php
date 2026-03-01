<?php

declare(strict_types=1);

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogPost extends Model
{
    use Translatable;

    public $translatedAttributes = [
        'title',
        'slug',
        'excerpt',
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_title',
        'og_description',
        'og_image_path',
    ];

    public $translationModel = BlogPostTranslation::class;

    protected $fillable = [
        'slug',
        'blog_category_id',
        'author_id',
        'status',
        'published_at',
        'scheduled_at',
        'is_featured',
        'cover_image_path',
        'reading_time_minutes',
        'allow_comments',
        'views_count',
        'likes_count',
        'canonical_url',
        'robots',
        'seo_indexable',
        'seo_follow',
        'og_image',
        'is_published',
        'order_column',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'allow_comments' => 'boolean',
        'seo_indexable' => 'boolean',
        'seo_follow' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'reading_time_minutes' => 'integer',
        'order_column' => 'integer',
    ];

    /**
     * Boot the model and handle slug generation
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (BlogPost $post) {
            // Auto-generate slug from title if empty
            if (empty($post->slug) && !empty($post->title)) {
                $post->slug = Str::slug($post->title);
                
                // Ensure unique slug
                $originalSlug = $post->slug;
                $counter = 2;
                while (static::where('slug', $post->slug)->where('id', '!=', $post->id)->exists()) {
                    $post->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }

    /**
     * Get the post's category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    /**
     * Get the post's author
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Get the post's tags
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(BlogTag::class, 'blog_post_blog_tag');
    }

    /**
     * Get the post's likes
     */
    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class, 'blog_post_id');
    }

    /**
     * Scope: Published posts only
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where('published_at', '<=', now());
    }

    /**
     * Scope: Featured posts only
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope: Scheduled posts
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '>', now());
    }

    /**
     * Get cover image URL or fallback
     */
    public function getCoverImageUrlAttribute(): string
    {
        if ($this->cover_image_path) {
            if (str_starts_with($this->cover_image_path, 'http')) {
                return $this->cover_image_path;
            }
            // Asset paths (legacy seeded data) — serve from public/
            if (str_starts_with($this->cover_image_path, 'assets/')) {
                return asset($this->cover_image_path);
            }
            // Storage paths — serve from public disk
            return Storage::disk('public')->url($this->cover_image_path);
        }
        return asset('assets/images/blog-1.png');
    }
    
    /**
     * Check if cover image file exists
     */
    public function getCoverImageExistsAttribute(): bool
    {
        if (!$this->cover_image_path) {
            return false;
        }
        if (str_starts_with($this->cover_image_path, 'assets/')) {
            return file_exists(public_path($this->cover_image_path));
        }
        return Storage::disk('public')->exists($this->cover_image_path);
    }

    /**
     * Get formatted published date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->published_at 
            ? $this->published_at->format('M d, Y') 
            : '';
    }
}
