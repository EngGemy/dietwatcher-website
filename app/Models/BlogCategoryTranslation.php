<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlogCategoryTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'description'];
}
