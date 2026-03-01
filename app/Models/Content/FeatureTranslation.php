<?php

declare(strict_types=1);

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $feature_id
 * @property string $locale
 * @property string $title
 * @property string|null $description
 */
class FeatureTranslation extends Model
{
    public $timestamps = true;

    protected $fillable = ['title', 'description'];

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }
}
