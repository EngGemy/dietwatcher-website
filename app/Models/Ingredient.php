<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Astrotomic\Translatable\Translatable;

class Ingredient extends Model
{
    use Translatable;

    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public array $translatable = ['name'];

    public function meals(): BelongsToMany
    {
        return $this->belongsToMany(Meal::class, 'ingredient_meal')
            ->withPivot(['quantity', 'allow_print', 'is_main_ingredient']);
    }
}
