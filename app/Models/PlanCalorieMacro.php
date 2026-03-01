<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanCalorieMacro extends Model
{
    protected $fillable = [
        'plan_calorie_id',
        'calories',
        'protein_g',
        'carbs_g',
        'fat_g',
    ];

    protected $casts = [
        'calories' => 'integer',
        'protein_g' => 'decimal:2',
        'carbs_g' => 'decimal:2',
        'fat_g' => 'decimal:2',
    ];

    public function planCalorie(): BelongsTo
    {
        return $this->belongsTo(PlanCalorie::class);
    }
}
