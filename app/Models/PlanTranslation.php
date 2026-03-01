<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanTranslation extends Model
{
    protected $fillable = [
        'plan_id',
        'locale',
        'name',
        'subtitle',
        'description',
        'ingredients',
        'benefits',
    ];

    public $timestamps = true;
}
