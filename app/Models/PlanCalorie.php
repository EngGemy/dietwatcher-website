<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PlanCalorie extends Model
{
    protected $fillable = [
        'plan_id',
        'min_amount',
        'max_amount',
        'is_active',
        'is_default',
        'order_column',
    ];

    protected $casts = [
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'order_column' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function macros(): HasOne
    {
        return $this->hasOne(PlanCalorieMacro::class);
    }

    public function getRangeTextAttribute(): string
    {
        return "{$this->min_amount}-{$this->max_amount}";
    }

    // Ensure only one default per plan
    protected static function booted(): void
    {
        static::saving(function (PlanCalorie $calorie) {
            if ($calorie->is_default) {
                static::where('plan_id', $calorie->plan_id)
                    ->where('id', '!=', $calorie->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
