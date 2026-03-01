<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'type',
        'value',
        'min_order_amount',
        'max_discount_amount',
        'max_uses',
        'max_uses_per_user',
        'used_count',
        'is_active',
        'starts_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'max_discount_amount' => 'integer',
            'max_uses' => 'integer',
            'max_uses_per_user' => 'integer',
            'used_count' => 'integer',
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Check if coupon is valid (active, within date range, under max uses).
     */
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    /**
     * Check if coupon is valid for a specific user (per-user limit).
     */
    public function isValidForUser(string $identifier): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->max_uses_per_user === null) {
            return true;
        }

        $userUses = DB::table('coupon_uses')
            ->where('coupon_id', $this->id)
            ->where('identifier', $identifier)
            ->count();

        return $userUses < $this->max_uses_per_user;
    }

    /**
     * Calculate discount amount in SAR for a given subtotal in SAR.
     * Returns 0 if min_order_amount not met.
     */
    public function calculateDiscount(float $subtotal): float
    {
        // Check minimum order amount (convert from halalas to SAR for comparison)
        if ($this->min_order_amount !== null && $subtotal < ($this->min_order_amount / 100)) {
            return 0;
        }

        if ($this->type === 'percentage') {
            // value stores percentage points (e.g. 10 for 10%)
            $discount = $subtotal * ($this->value / 100);
        } else {
            // fixed: value is in halalas, convert to SAR
            $discount = $this->value / 100;
        }

        // Cap at max_discount_amount if set (convert from halalas to SAR)
        if ($this->max_discount_amount !== null) {
            $maxDiscount = $this->max_discount_amount / 100;
            $discount = min($discount, $maxDiscount);
        }

        // Discount cannot exceed subtotal
        return min($discount, $subtotal);
    }

    /**
     * Increment usage after successful payment.
     */
    public function incrementUsage(string $identifier): void
    {
        $this->increment('used_count');

        DB::table('coupon_uses')->insert([
            'coupon_id' => $this->id,
            'identifier' => $identifier,
            'used_at' => now(),
        ]);
    }
}
