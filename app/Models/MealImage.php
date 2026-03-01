<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealImage extends Model
{
    protected $fillable = [
        'meal_id',
        'image_path',
        'is_cover',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'is_cover' => 'boolean',
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    public function meal(): BelongsTo
    {
        return $this->belongsTo(Meal::class);
    }

    protected static function booted(): void
    {
        static::saving(function (MealImage $image) {
            if ($image->is_cover) {
                static::where('meal_id', $image->meal_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_cover' => false]);
            }
        });
    }
}
