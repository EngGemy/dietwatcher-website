<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestimonialTranslation extends Model
{
    public $timestamps = false; // Typically translations table don't need timestamps unless schema has them. Migration HAS timestamps.

    protected $fillable = [
        'author_name',
        'author_title',
        'content',
    ];
}
