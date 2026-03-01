<?php

declare(strict_types=1);

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalPlanProgram extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     */
    protected $connection = 'external_mysql';

    /**
     * The table associated with the model.
     */
    protected $table = 'plan_programs';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'category_id',
        'name',
        'name_ar',
        'description',
        'description_ar',
        'image',
        'price',
        'duration_days',
        'meals_per_day',
        'calories_per_day',
        'is_active',
        'order',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'meals_per_day' => 'integer',
        'calories_per_day' => 'integer',
    ];

    /**
     * Scope a query to only include active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the image URL attribute.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            if (str_starts_with($this->image, 'http')) {
                return $this->image;
            }
            return 'http://157.90.252.39/storage/' . $this->image;
        }
        return asset('assets/images/meal-plan-1.png');
    }

    /**
     * Get localized name
     */
    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->name_ar ? $this->name_ar : $this->name;
    }

    /**
     * Get localized description
     */
    public function getLocalizedDescriptionAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'ar' && $this->description_ar ? $this->description_ar : $this->description;
    }

    /**
     * Get the category that owns this program.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExternalCategory::class, 'category_id');
    }

    /**
     * Get the details for this program.
     */
    public function details(): HasMany
    {
        return $this->hasMany(ExternalProgramDetail::class, 'program_id');
    }
}
