<?php

declare(strict_types=1);

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MealType extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'slug',
        'order_column',
    ];

    protected $casts = [
        'order_column' => 'integer',
    ];

    /** @var list<string> */
    public array $translatedAttributes = ['name'];

    /** @var string */
    public string $translationForeignKey = 'meal_type_id';

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'meal_type_plan');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_column');
    }
}
