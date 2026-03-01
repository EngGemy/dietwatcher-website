<?php

declare(strict_types=1);

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalCategory extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     */
    protected $connection = 'external_mysql';

    /**
     * The table associated with the model.
     */
    protected $table = 'categories';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'icon',
        'is_active',
        'type',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'name' => 'array', // JSON field with en/ar
    ];

    /**
     * Scope a query to only include active categories.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Get the image/icon URL attribute.
     */
    public function getImageUrlAttribute(): string
    {
        if ($this->icon) {
            if (str_starts_with($this->icon, 'http')) {
                return $this->icon;
            }
            // Images are stored on the external server
            return 'http://157.90.252.39/storage/' . $this->icon;
        }
        return asset('assets/images/meal-plan-1.png');
    }

    /**
     * Get localized name from JSON
     */
    public function getLocalizedNameAttribute(): string
    {
        $nameData = is_string($this->name) ? json_decode($this->name, true) : $this->name;
        $locale = app()->getLocale();
        
        if ($locale === 'ar' && isset($nameData['ar'])) {
            return $nameData['ar'];
        }
        
        return $nameData['en'] ?? $nameData[array_key_first($nameData)] ?? '';
    }

    /**
     * Get English name
     */
    public function getNameEnAttribute(): ?string
    {
        $nameData = is_string($this->name) ? json_decode($this->name, true) : $this->name;
        return $nameData['en'] ?? null;
    }

    /**
     * Get Arabic name
     */
    public function getNameArAttribute(): ?string
    {
        $nameData = is_string($this->name) ? json_decode($this->name, true) : $this->name;
        return $nameData['ar'] ?? null;
    }
}
