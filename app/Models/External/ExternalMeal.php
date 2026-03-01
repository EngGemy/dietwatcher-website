<?php

declare(strict_types=1);

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalMeal extends Model
{
    use HasFactory;

    protected $connection = 'external_mysql';
    protected $table = 'meals';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'description_ar',
        'price',
        'calories',
        'protein',
        'carbs',
        'fats',
        'image',
        'is_active',
        'category_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'calories' => 'integer',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fats' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'http')) {
                return $this->image;
            }
            return 'http://157.90.252.39/storage/' . $this->image;
        }
        return asset('assets/images/meal-1.png');
    }
}
