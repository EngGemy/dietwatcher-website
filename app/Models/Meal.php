<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Astrotomic\Translatable\Translatable;

class Meal extends Model
{
    use Translatable;

    protected $fillable = [
        'meal_group_id',
        'name',
        'description',
        'price',
        'calories',
        'protein',
        'carbs',
        'fat',
        'image',
        'is_active',
        'is_store_product',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'calories' => 'integer',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fat' => 'decimal:2',
        'is_active' => 'boolean',
        'is_store_product' => 'boolean',
    ];

    public array $translatable = ['name', 'description'];

    // Relationships

    public function group(): BelongsTo
    {
        return $this->belongsTo(MealGroup::class, 'meal_group_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(MealImage::class)->orderBy('order_column');
    }

    public function activeImages(): HasMany
    {
        return $this->images()->where('is_active', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_meal');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(MealTag::class, 'meal_meal_tag');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(MealGroup::class, 'meal_meal_group');
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'ingredient_meal')
            ->withPivot(['quantity', 'allow_print', 'is_main_ingredient']);
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'meal_offer');
    }

    public function activeOffers(): BelongsToMany
    {
        return $this->offers()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    // Helpers

    public function getCoverImageAttribute(): ?string
    {
        $image = $this->images->where('is_cover', true)->first()
            ?? $this->activeImages->first();

        return $image ? Storage::url($image->image_path) : ($this->image ? Storage::url($this->image) : null);
    }

    // Scopes

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeStoreProduct(Builder $query): Builder
    {
        return $query->where('is_store_product', true);
    }
}
