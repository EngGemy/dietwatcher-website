<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];
}
