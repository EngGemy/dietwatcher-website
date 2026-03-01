<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $hero_section_id
 * @property string $locale
 * @property string $title
 * @property string|null $subtitle
 * @property string|null $cta_text
 * @property string|null $cta_secondary_text
 */
class HeroSectionTranslation extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'title',
        'subtitle',
        'cta_text',
        'cta_secondary_text',
    ];

    public function heroSection(): BelongsTo
    {
        return $this->belongsTo(HeroSection::class);
    }
}
