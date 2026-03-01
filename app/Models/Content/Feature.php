<?php

declare(strict_types=1);

namespace App\Models\Content;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'order',
        'image',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /** @var list<string> */
    public array $translatedAttributes = ['title', 'description'];

    /** @var string */
    public string $translationForeignKey = 'feature_id';

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        return null;
    }
}
