<?php

declare(strict_types=1);

namespace App\Models\Content;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class HeroSection extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'order',
        'image_desktop',
        'image_mobile',
        'app_store_url',
        'play_store_url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /** @var list<string> */
    public array $translatedAttributes = [
        'title',
        'subtitle',
        'cta_text',
        'cta_secondary_text',
    ];

    /** @var string */
    public string $translationForeignKey = 'hero_section_id';
}
