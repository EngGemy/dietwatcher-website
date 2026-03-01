<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqSectionHeader extends Model
{
    protected $fillable = [
        'badge_title_en',
        'badge_title_ar',
        'title_en',
        'title_ar',
        'subtitle_en',
        'subtitle_ar',
        'is_active',
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

    protected static function booted()
    {
        static::saving(function ($header) {
            if ($header->is_active) {
                static::where('id', '!=', $header->id)->update(['is_active' => false]);
            }
        });
    }
}
