<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BlogPostTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
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

    /**
     * Boot the model and handle slug generation per locale
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (BlogPostTranslation $translation) {
            // Auto-generate slug from title if empty
            if (empty($translation->slug) && !empty($translation->title)) {
                $translation->slug = Str::slug($translation->title);
                
                // Ensure unique slug per locale
                $originalSlug = $translation->slug;
                $counter = 2;
                while (static::where('slug', $translation->slug)
                    ->where('locale', $translation->locale)
                    ->where('id', '!=', $translation->id)
                    ->exists()) {
                    $translation->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }
        });
    }
}
