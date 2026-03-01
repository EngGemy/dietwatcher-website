<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HowItWorksStep extends Model
{
    protected $fillable = [
        'image',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'order_column',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    /**
     * Get title based on current locale
     */
    public function title($locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $locale === 'ar' ? $this->title_ar : $this->title_en;
    }

    /**
     * Get description based on current locale
     */
    public function description($locale = null): string
    {
        $locale = $locale ?: app()->getLocale();
        return $locale === 'ar' ? $this->description_ar : $this->description_en;
    }

    /**
     * Get image URL
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return asset('assets/images/how-old-1.png');
    }

    /**
     * Scope: Active steps only
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Ordered by order_column
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column');
    }
}
