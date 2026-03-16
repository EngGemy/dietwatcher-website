<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Settings\Setting;
use App\Services\ExternalDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const DURATION_MULTIPLIERS = [
        'once' => 1,
        'weekly' => 0.25,
        'monthly' => 1,
        '3months' => 3,
    ];

    public function __construct(
        private ExternalDataService $externalDataService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        // Direct plan checkout via query params (from "Subscribe Now" button)
        if ($request->has('plan_id')) {
            $planId = (int) $request->get('plan_id');
            $plan = $this->externalDataService->getProgram($planId);

            if (!$plan) {
                return redirect()->route('meal-plans.index')
                    ->with('error', __('Plan not found'));
            }

            $locale = app()->getLocale();

            // Parse plan name (could be JSON or plain string)
            $rawName = $plan->name ?? '';
            if (is_string($rawName)) {
                $decoded = json_decode($rawName, true);
                $planName = is_array($decoded) ? ($decoded[$locale] ?? $decoded['en'] ?? $rawName) : $rawName;
            } elseif (is_array($rawName)) {
                $planName = $rawName[$locale] ?? $rawName['en'] ?? '';
            } else {
                $planName = (string) $rawName;
            }

            // Resolve image URL
            $imageUrl = $plan->image_url ?? '';
            if (!str_starts_with($imageUrl, 'http')) {
                $imageUrl = $imageUrl ? asset($imageUrl) : asset('assets/images/plan-1.png');
            }

            // Build cart with this single plan
            $mealType = $request->get('meal_type', 'breakfast');
            $calories = $request->get('calories', '');
            $durationId = $request->get('duration_id', '');

            $cart = [
                'plan_' . $planId => [
                    'id' => $planId,
                    'name' => $planName,
                    'price' => (float) ($plan->price ?? 0),
                    'image' => $imageUrl,
                    'quantity' => 1,
                    'options' => [
                        'mealType' => $mealType,
                        'calories' => $calories,
                        'duration_days' => $plan->duration_days ?? 28,
                        'duration_id' => $durationId,
                    ],
                ],
            ];

            session()->put('cart', $cart);
        }

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('meal-plans.index')
                ->with('error', __('Your cart is empty'));
        }

        // Determine if cart has plan items (plans get free delivery)
        $hasPlanItems = collect($cart)->contains(fn($item) => !empty($item['options']['duration_days']));

        // Calculate base subtotal (per-item total, no duration multiplier)
        $baseSubtotal = 0;
        foreach ($cart as $item) {
            $baseSubtotal += $item['price'] * $item['quantity'];
        }

        $vatRate = (float) Setting::getValue('vat_rate', 15) / 100;

        // Fetch dynamic zones from API
        $zones = $this->externalDataService->getZones();

        // Fallback to hardcoded cities if API returns empty
        if (empty($zones)) {
            $zones = [
                ['id' => 1, 'name' => __('Riyadh'), 'subscription_delivery_price' => 0, 'order_delivery_price' => 25, 'is_active' => true],
                ['id' => 2, 'name' => __('Jeddah'), 'subscription_delivery_price' => 0, 'order_delivery_price' => 25, 'is_active' => true],
                ['id' => 3, 'name' => __('Dammam'), 'subscription_delivery_price' => 0, 'order_delivery_price' => 25, 'is_active' => true],
                ['id' => 4, 'name' => __('Al Khobar'), 'subscription_delivery_price' => 0, 'order_delivery_price' => 25, 'is_active' => true],
                ['id' => 5, 'name' => __('Makkah'), 'subscription_delivery_price' => 0, 'order_delivery_price' => 25, 'is_active' => true],
                ['id' => 6, 'name' => __('Madinah'), 'subscription_delivery_price' => 0, 'order_delivery_price' => 25, 'is_active' => true],
            ];
        }

        // Plans = delivery included in price, Meals = fee from zone
        $deliveryFeeAmount = $hasPlanItems ? 0 : (float) Setting::getValue('delivery_fee', 25);

        // Fetch plan durations if cart has plan items
        $planDurations = [];
        if ($hasPlanItems) {
            $firstPlanItem = collect($cart)->first(fn($item) => !empty($item['options']['duration_days']));
            if ($firstPlanItem) {
                $planDurations = $this->externalDataService->getPlanDurations($firstPlanItem['id']);
            }
        }

        $durationMultipliers = self::DURATION_MULTIPLIERS;

        return view('pages.checkout', compact(
            'cart',
            'baseSubtotal',
            'deliveryFeeAmount',
            'vatRate',
            'zones',
            'durationMultipliers',
            'planDurations'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|string|max:50',
            'duration' => 'required|in:once,weekly,monthly,3months',
            'delivery_type' => 'required|in:home,pickup',
            'coupon' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'zone_id' => 'required_if:delivery_type,home|nullable|integer',
            'street' => 'required_if:delivery_type,home|nullable|string|max:255',
            'building' => 'nullable|string|max:255',
        ]);

        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('meal-plans.index')
                ->with('error', __('Your cart is empty'));
        }

        // Calculate base subtotal
        $baseSubtotal = 0;
        foreach ($cart as $item) {
            $baseSubtotal += $item['price'] * $item['quantity'];
        }

        // Determine if cart has plan items (plans get free delivery)
        $hasPlanItems = collect($cart)->contains(fn($item) => !empty($item['options']['duration_days']));

        // Apply duration multiplier
        $multiplier = self::DURATION_MULTIPLIERS[$validated['duration']] ?? 1;
        $subtotal = round($baseSubtotal * $multiplier, 2);

        // Get delivery fee from zone if provided, otherwise from settings
        $deliveryFeeFromSettings = $hasPlanItems ? 0 : (float) Setting::getValue('delivery_fee', 25);
        $zoneDeliveryFee = 0.0;
        $zoneName = null;

        if ($validated['delivery_type'] === 'home' && !empty($validated['zone_id'])) {
            $zones = $this->externalDataService->getZones();
            $selectedZone = collect($zones)->firstWhere('id', (int) $validated['zone_id']);
            if ($selectedZone) {
                $zoneName = $selectedZone['name'];
                $zoneDeliveryFee = $hasPlanItems
                    ? (float) $selectedZone['subscription_delivery_price']
                    : (float) $selectedZone['order_delivery_price'];
            }
        }

        $deliveryFee = $validated['delivery_type'] === 'home'
            ? ($zoneDeliveryFee > 0 ? $zoneDeliveryFee : $deliveryFeeFromSettings)
            : 0.0;

        // Handle coupon discount
        $discountAmount = 0.0;
        $coupon = null;
        $couponCode = $validated['coupon'] ?? null;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();

            if ($coupon) {
                $identifier = $validated['email'];

                if ($coupon->isValidForUser($identifier)) {
                    $discountAmount = $coupon->calculateDiscount($subtotal);
                }
            }
        }

        // Prices from API are VAT-INCLUSIVE (like mobile app)
        // Extract VAT from the inclusive price for record-keeping
        $vatRate = (float) Setting::getValue('vat_rate', 15) / 100;
        $total = $subtotal + $deliveryFee - $discountAmount;
        // VAT is extracted from the inclusive total: VAT = total - (total / (1 + vatRate))
        $vatAmount = round($total - ($total / (1 + $vatRate)), 2);

        // Convert to halalas (smallest currency unit) for Moyasar
        $amountInHalalas = (int) round($total * 100);

        // Create payment record
        $payment = Payment::create([
            'order_number' => Payment::generateOrderNumber(),
            'status' => 'pending',
            'amount' => $amountInHalalas,
            'currency' => 'SAR',
            'subtotal' => (int) round($subtotal * 100),
            'delivery_fee' => (int) round($deliveryFee * 100),
            'vat_amount' => (int) round($vatAmount * 100),
            'discount_amount' => (int) round($discountAmount * 100),
            'customer_name' => $validated['name'],
            'customer_email' => $validated['email'],
            'customer_phone' => $validated['phone'],
            'cart_items' => $cart,
            'start_date' => $validated['start_date'],
            'duration' => $validated['duration'],
            'delivery_type' => $validated['delivery_type'],
            'city' => $zoneName ?? ($validated['zone_id'] ?? null),
            'street' => $validated['street'] ?? null,
            'building' => $validated['building'] ?? null,
            'coupon' => $couponCode,
            'expires_at' => now()->addMinutes(30),
        ]);

        // Increment coupon usage after creating payment
        if ($coupon && $discountAmount > 0) {
            $coupon->incrementUsage($validated['email']);
        }

        // Redirect to Moyasar payment form
        return redirect()->route('payment.form', ['order' => $payment->order_number]);
    }

    /**
     * AJAX endpoint to validate and apply a coupon code.
     */
    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'subtotal' => 'required|numeric|min:0',
            'identifier' => 'required|string|max:255',
        ]);

        $coupon = Coupon::where('code', strtoupper($validated['code']))->first();

        if (!$coupon) {
            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => __('Invalid coupon code.'),
            ]);
        }

        if (!$coupon->isValid()) {
            $message = __('This coupon is no longer valid.');

            if ($coupon->expires_at && $coupon->expires_at->isPast()) {
                $message = __('This coupon has expired.');
            }

            if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
                $message = __('This coupon has been fully redeemed.');
            }

            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => $message,
            ]);
        }

        if (!$coupon->isValidForUser($validated['identifier'])) {
            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => __('You have already used this coupon the maximum number of times.'),
            ]);
        }

        $discount = $coupon->calculateDiscount((float) $validated['subtotal']);

        if ($discount <= 0) {
            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => __('Your order does not meet the minimum amount for this coupon.'),
            ]);
        }

        return response()->json([
            'valid' => true,
            'discount' => round($discount, 2),
            'message' => __('Coupon applied successfully!'),
            'type' => $coupon->type,
            'value' => $coupon->type === 'percentage' ? $coupon->value : ($coupon->value / 100),
        ]);
    }
}
