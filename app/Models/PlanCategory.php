<?php

declare(strict_types=1);

namespace App\Models;

use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlanCategory extends Model implements TranslatableContract
{
    use Translatable;

    protected $fillable = [
        'slug',
        'is_active',
        'order_column',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order_column' => 'integer',
    ];

    /** @var list<string> */
    public array $translatedAttributes = ['name'];

    /** @var string */
    public string $translationForeignKey = 'plan_category_id';

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_category');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_column');
    }
}
