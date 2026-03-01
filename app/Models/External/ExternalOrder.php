<?php

declare(strict_types=1);

namespace App\Models\External;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalOrder extends Model
{
    use HasFactory;

    protected $connection = 'external_mysql';
    protected $table = 'orders';
    public $timestamps = true;

    protected $fillable = [
        'customer_id',
        'order_number',
        'status',
        'total_amount',
        'subtotal',
        'tax',
        'discount',
        'payment_status',
        'payment_method',
        'notes',
        'order_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'order_date' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ExternalCustomer::class, 'customer_id');
    }
}
