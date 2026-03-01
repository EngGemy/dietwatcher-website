<?php

declare(strict_types=1);

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use Translatable;

    public $translatedAttributes = ['author_name', 'author_title', 'content'];

    // Explicitly define translation model so Astrotomic finds it easily
    public $translationModel = TestimonialTranslation::class;

    protected $fillable = [
        'author_image',
        'rating',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getAuthorImageUrlAttribute(): ?string
    {
        if ($this->author_image) {
            return asset('storage/' . $this->author_image);
        }
        return null;
    }
}
