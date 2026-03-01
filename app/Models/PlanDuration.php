<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Settings\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlanDuration extends Model
{
    protected $fillable = [
        'plan_id',
        'service_id',
        'service_price',
        'days',
        'price',
        'delivery_price',
        'start_date',
        'currency',
        'is_active',
        'is_default',
        'order_column',
    ];

    protected $casts = [
        'service_price' => 'decimal:2',
        'days' => 'integer',
        'price' => 'decimal:2',
        'delivery_price' => 'decimal:2',
        'start_date' => 'date',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'order_column' => 'integer',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(Offer::class, 'plan_duration_offer');
    }

    // Price calculation helpers
    public function getOfferPriceAttribute(): float
    {
        $activeOffer = $this->offers()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->first();

        if (!$activeOffer) {
            return (float) $this->price;
        }

        $discountAmount = $activeOffer->discount_amount > 0
            ? $activeOffer->discount_amount
            : ($this->price * $activeOffer->discount_percentage / 100);

        return max(0, (float) $this->price - $discountAmount);
    }

    public function getSubtotalAttribute(): float
    {
        return $this->offer_price + (float) $this->delivery_price;
    }

    public function getVatAmountAttribute(): float
    {
        $vatRate = (float) Setting::getValue('vat_rate', 15) / 100;
        return $this->subtotal * $vatRate;
    }

    public function getTotalAttribute(): float
    {
        return $this->subtotal + $this->vat_amount;
    }

    public function priceAsStringWithoutCurrency(): string
    {
        return number_format((float) $this->price, 2);
    }

    // Ensure only one default per plan
    protected static function booted(): void
    {
        static::saving(function (PlanDuration $duration) {
            if ($duration->is_default) {
                static::where('plan_id', $duration->plan_id)
                    ->where('id', '!=', $duration->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
