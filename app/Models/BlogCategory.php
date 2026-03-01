<?php

declare(strict_types=1);

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlogCategory extends Model
{
    use Translatable;

    public $translatedAttributes = ['name', 'description'];
    public $translationModel = BlogCategoryTranslation::class;

    protected $fillable = [
        'slug',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }
}
