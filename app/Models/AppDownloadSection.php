<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppDownloadSection extends Model
{
    protected $fillable = [
        'badge_title_en',
        'badge_title_ar',
        'title_en',
        'title_ar',
        'subtitle_en',
        'subtitle_ar',
        'mobile_image',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function badge_title($locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $locale === 'ar' ? $this->badge_title_ar : $this->badge_title_en;
    }

    public function title($locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $locale === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function subtitle($locale = null)
    {
        $locale = $locale ?: app()->getLocale();
        return $locale === 'ar' ? $this->subtitle_ar : $this->subtitle_en;
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        if ($this->mobile_image) {
            return asset('storage/' . $this->mobile_image);
        }
        return asset('assets/images/app-screens.png');
    }

    protected static function booted()
    {
        static::saving(function ($section) {
            if ($section->is_active) {
                static::where('id', '!=', $section->id)->update(['is_active' => false]);
            }
        });
    }
}
