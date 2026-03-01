<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MealTypeTranslation extends Model
{
    protected $fillable = [
        'meal_type_id',
        'locale',
        'name',
    ];

    public $timestamps = true;
}
