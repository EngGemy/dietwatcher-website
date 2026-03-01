<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanImage extends Model
{
    protected $fillable = [
        'plan_id',
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

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // Ensure only one cover image per plan
    protected static function booted(): void
    {
        static::saving(function (PlanImage $image) {
            if ($image->is_cover) {
                // Set all other images for this plan to not be cover
                static::where('plan_id', $image->plan_id)
                    ->where('id', '!=', $image->id)
                    ->update(['is_cover' => false]);
            }
        });
    }
}
