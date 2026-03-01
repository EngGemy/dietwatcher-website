<?php

declare(strict_types=1);

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalUser extends Model
{
    use HasFactory;

    protected $connection = 'external_mysql';
    protected $table = 'users';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'is_active',
        'email_verified_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'email_verified_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
