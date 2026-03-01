<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Offer extends Model
{
    protected $fillable = [
        'name',
        'discount_percentage',
        'discount_amount',
        'is_active',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function planDurations(): BelongsToMany
    {
        return $this->belongsToMany(PlanDuration::class, 'plan_duration_offer');
    }
}
