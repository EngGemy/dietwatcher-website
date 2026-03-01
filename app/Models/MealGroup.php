<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Astrotomic\Translatable\Translatable;

class MealGroup extends Model
{
    use Translatable;

    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public array $translatable = ['name'];

    public function meals(): HasMany
    {
        return $this->hasMany(Meal::class);
    }
}
