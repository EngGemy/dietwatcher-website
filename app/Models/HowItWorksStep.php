<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . ltrim($this->image, '/'));
        }

        $fallbacks = [
            1 => ['assets/images/how-old-1.png', 'assets/images/how-1.png'],
            2 => ['assets/images/how-old-2.png', 'assets/images/how-2.png'],
            3 => ['assets/images/how-old-3.png', 'assets/images/how-3.png'],
        ];
        $idx = max(1, min(3, (int) ($this->order_column ?: 1)));

        $candidates = $fallbacks[$idx] ?? $fallbacks[1];
        foreach ($candidates as $path) {
            if (is_file(public_path($path))) {
                return asset($path);
            }
        }

        return asset('assets/images/plan-1.png');
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
