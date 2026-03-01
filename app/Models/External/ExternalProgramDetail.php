<?php

declare(strict_types=1);

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalProgramDetail extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     */
    protected $connection = 'external_mysql';

    /**
     * The table associated with the model.
     */
    protected $table = 'program_details';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'program_id',
        'day_number',
        'meal_type',
        'meal_name',
        'meal_name_ar',
        'description',
        'description_ar',
        'calories',
        'protein',
        'carbs',
        'fats',
        'image',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'day_number' => 'integer',
        'calories' => 'integer',
        'protein' => 'decimal:2',
        'carbs' => 'decimal:2',
        'fats' => 'decimal:2',
    ];

    /**
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'http')) {
                return $this->image;
            }
            return 'http://157.90.252.39/storage/' . $this->image;
        }
        return null;
    }

    /**
     * Get localized meal name
     */
    public function getLocalizedMealNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->meal_name_ar ? $this->meal_name_ar : $this->meal_name;
    }

    /**
     * Get localized description
     */
    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->description_ar ? $this->description_ar : $this->description;
    }

    /**
     * Get the program that owns this detail.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(ExternalPlanProgram::class, 'program_id');
    }
}
