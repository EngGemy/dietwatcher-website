<?php

declare(strict_types=1);

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaqCategory extends Model
{
    use Translatable;

    public $translatedAttributes = ['name'];
    public $translationModel = FaqCategoryTranslation::class;

    protected $fillable = [
        'slug',
        'icon',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    public function faqs(): HasMany
    {
        return $this->hasMany(Faq::class);
    }
}
