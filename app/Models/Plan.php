<?php

declare(strict_types=1);

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Plan extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'is_active',
        'show_in_app',
        'order_column',
        'hero_image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_in_app' => 'boolean',
        'order_column' => 'integer',
    ];

    /** @var list<string> */
    public array $translatedAttributes = [
        'name',
        'subtitle',
        'description',
        'ingredients',
        'benefits',
    ];

    /** @var string */
    public string $translationForeignKey = 'plan_id';

    // Relationships
    public function images(): HasMany
    {
        return $this->hasMany(PlanImage::class)->orderBy('order_column');
    }

    public function galleryImages(): HasMany
    {
        return $this->hasMany(PlanImage::class)
            ->where('is_active', true)
            ->orderBy('order_column');
    }

    public function calories(): HasMany
    {
        return $this->hasMany(PlanCalorie::class)->orderBy('order_column');
    }

    public function durations(): HasMany
    {
        return $this->hasMany(PlanDuration::class)->orderBy('order_column');
    }

    public function menus(): HasMany
    {
        return $this->hasMany(PlanMenu::class)->orderBy('order_column');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(PlanCategory::class, 'plan_category');
    }

    public function mealTypes(): BelongsToMany
    {
        return $this->belongsToMany(MealType::class, 'meal_type_plan');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeShowInApp($query)
    {
        return $query->where('show_in_app', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_column');
    }

    public function scopeByCategory($query, ?string $categorySlug = null)
    {
        if (!$categorySlug || $categorySlug === 'all') {
            return $query;
        }

        return $query->whereHas('categories', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    // Accessors & Helpers
    public function getCoverImageUrlAttribute(): ?string
    {
        $coverImage = $this->images()->where('is_cover', true)->first();

        if (!$coverImage) {
            $coverImage = $this->images()->where('is_active', true)->first();
        }

        if ($coverImage && $coverImage->image_path) {
            return Storage::url($coverImage->image_path);
        }

        if ($this->hero_image) {
            return Storage::url($this->hero_image);
        }

        return null;
    }

    public function defaultCalory(): ?PlanCalorie
    {
        return $this->calories()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? $this->calories()->where('is_active', true)->first();
    }

    public function defaultDuration(): ?PlanDuration
    {
        return $this->durations()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? $this->durations()->where('is_active', true)->first();
    }

    // Card display helpers
    public function getCardCaloriesTextAttribute(): string
    {
        $calory = $this->defaultCalory();

        if (!$calory) {
            return '';
        }

        return "{$calory->min_amount}-{$calory->max_amount} kcal/day";
    }

    public function getCardDaysTextAttribute(): string
    {
        $duration = $this->defaultDuration();

        if (!$duration) {
            return '';
        }

        return "{$duration->days} Days";
    }

    public function getCardPriceTextAttribute(): string
    {
        $duration = $this->defaultDuration();

        if (!$duration) {
            return '';
        }

        return "{$duration->currency} " . number_format((float) $duration->price, 2);
    }

    public function getCardStartingFromTextAttribute(): ?string
    {
        $duration = $this->defaultDuration();

        if (!$duration || !$duration->start_date) {
            return null;
        }

        return $duration->start_date->format('D d M');
    }
}
