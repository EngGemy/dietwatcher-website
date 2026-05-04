<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_number',
        'moyasar_id',
        'status',
        'payment_method',
        'amount',
        'currency',
        'subtotal',
        'delivery_fee',
        'vat_amount',
        'discount_amount',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_phone_normalized',
        'cart_items',
        'checkout_payload',
        'start_date',
        'duration',
        'delivery_type',
        'city',
        'street',
        'building',
        'coupon',
        'description',
        'source_type',
        'card_type',
        'masked_pan',
        'message',
        'raw_response',
        'external_order_id',
        'external_order_number',
        'external_sync_status',
        'external_sync_message',
        'external_synced_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'payment_method' => PaymentMethod::class,
            'cart_items' => 'array',
            'checkout_payload' => 'array',
            'raw_response' => 'array',
            'amount' => 'integer',
            'subtotal' => 'integer',
            'delivery_fee' => 'integer',
            'vat_amount' => 'integer',
            'discount_amount' => 'integer',
            'external_order_id' => 'integer',
            'external_synced_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Amount in SAR (from halalas).
     */
    public function getAmountInSarAttribute(): float
    {
        return $this->amount / 100;
    }

    /**
     * Generate a unique order number.
     */
    public static function generateOrderNumber(): string
    {
        return 'DW-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Check if payment session is still valid.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', PaymentStatus::PAID);
    }
}
