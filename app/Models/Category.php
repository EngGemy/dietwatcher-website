<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Translatable;

class Category extends Model
{
    use Translatable;

    protected $fillable = ['name', 'type', 'icon', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public array $translatable = ['name'];

    public function meals(): BelongsToMany
    {
        return $this->belongsToMany(Meal::class, 'category_meal');
    }
}
