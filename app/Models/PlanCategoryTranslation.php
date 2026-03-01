<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlanCategoryTranslation extends Model
{
    protected $fillable = [
        'plan_category_id',
        'locale',
        'name',
    ];

    public $timestamps = true;
}
