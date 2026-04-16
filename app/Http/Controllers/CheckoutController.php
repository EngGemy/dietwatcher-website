<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Livewire\Cart\CartManager;
use App\Models\Coupon;
use App\Models\Payment;
use App\Models\Settings\Setting;
use App\Services\ApiAuthService;
use App\Services\ExternalDataService;
use App\Services\Payment\MoyasarPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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

            if (! $plan) {
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

            // Resolve image URL (absolute API path or local asset)
            $imageUrl = trim((string) ($plan->image_url ?? ''));
            $externalApiOrigin = rtrim(preg_replace('#/api/?$#i', '', (string) config('services.external_api.url', '')), '/');
            if ($imageUrl !== '' && ! str_starts_with($imageUrl, 'http') && ! str_starts_with($imageUrl, '//')) {
                if (str_starts_with($imageUrl, '/') && $externalApiOrigin !== '') {
                    $imageUrl = $externalApiOrigin.$imageUrl;
                } elseif (str_starts_with($imageUrl, '/')) {
                    $imageUrl = asset(ltrim($imageUrl, '/'));
                } else {
                    $imageUrl = asset($imageUrl);
                }
            }
            if ($imageUrl === '') {
                $imageUrl = asset('assets/images/plan-1.png');
            }

            // Build cart with this single plan
            $mealType = $request->get('meal_type', '');
            $calories = $request->get('calories', '');
            $durationId = (string) $request->get('duration_id', '');
            $durationDaysParam = (int) $request->get('duration_days', 0);
            $subscriptionPlanId = (int) $request->get('subscription_plan_id', 0);
            $planTotalParam = (float) $request->get('plan_total', 0);

            // Resolve duration days + id from API list (cart must not use $plan->duration_days alone — it is often the default e.g. 5 days)
            $apiDurations = $this->externalDataService->getPlanDurations($planId);
            $resolvedDurationDays = (int) ($plan->duration_days ?? 28);
            $resolvedDurationId = $durationId;

            if ($apiDurations !== []) {
                $byId = collect($apiDurations)->first(function (array $d) use ($durationId): bool {
                    return $durationId !== '' && (string) ($d['id'] ?? '') === (string) $durationId;
                });
                if ($byId !== null) {
                    $resolvedDurationDays = (int) ($byId['days'] ?? $resolvedDurationDays);
                } elseif ($durationDaysParam > 0) {
                    $byDays = collect($apiDurations)->first(function (array $d) use ($durationDaysParam): bool {
                        return (int) ($d['days'] ?? 0) === $durationDaysParam;
                    });
                    if ($byDays !== null) {
                        $resolvedDurationDays = (int) ($byDays['days'] ?? $durationDaysParam);
                        $resolvedDurationId = (string) ($byDays['id'] ?? $resolvedDurationId);
                    }
                }
            } elseif ($durationDaysParam > 0) {
                $resolvedDurationDays = $durationDaysParam;
            }

            $variantName = '';
            if ($subscriptionPlanId > 0 && isset($plan->subscription_plans)) {
                $variants = is_array($plan->subscription_plans)
                    ? $plan->subscription_plans
                    : json_decode(json_encode($plan->subscription_plans), true);
                foreach ($variants ?? [] as $sp) {
                    if ((int) ($sp['id'] ?? 0) === $subscriptionPlanId) {
                        $variantName = (string) ($sp['name'] ?? '');
                        break;
                    }
                }
            }

            $displayName = $variantName !== '' ? $planName.' — '.$variantName : $planName;
            $linePrice = $planTotalParam > 0 ? $planTotalParam : (float) ($plan->price ?? 0);

            $subscriptionCart = [
                'plan_'.$planId => [
                    'id' => $planId,
                    'name' => $displayName,
                    'price' => $linePrice,
                    'image' => $imageUrl,
                    'quantity' => 1,
                    'options' => [
                        'mealType' => $mealType,
                        'subscription_plan_id' => $subscriptionPlanId > 0 ? $subscriptionPlanId : null,
                        'calories' => $calories,
                        'duration_days' => $resolvedDurationDays,
                        'duration_id' => $resolvedDurationId !== '' ? $resolvedDurationId : $durationId,
                    ],
                ],
            ];

            session()->forget(CartManager::SESSION_MARKET);
            session()->put(CartManager::SESSION_SUBSCRIPTION, $subscriptionCart);
        } else {
            $market = session()->get(CartManager::SESSION_MARKET, []);
            if ($market !== []) {
                session()->forget(CartManager::SESSION_SUBSCRIPTION);
            }
        }

        $cart = session()->get(CartManager::SESSION_SUBSCRIPTION)
            ?? session()->get(CartManager::SESSION_MARKET, []);

        if (empty($cart)) {
            return redirect()->route('meals.index')
                ->with('error', __('Your cart is empty'));
        }

        // Determine if cart has plan items (plans get free delivery)
        $hasPlanItems = collect($cart)->contains(fn ($item) => ! empty($item['options']['duration_days']));

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

        // Fetch plan durations (API: GET /programs/{programId}/durations — always use meal plan id)
        $planDurations = [];
        $firstPlanItem = null;
        if ($hasPlanItems) {
            $firstPlanItem = collect($cart)->first(fn ($item) => ! empty($item['options']['duration_days']));
            if ($firstPlanItem) {
                $programId = (int) ($firstPlanItem['id'] ?? 0);
                if ($programId > 0) {
                    $rawDurations = $this->externalDataService->getPlanDurations($programId);
                    $planDurations = array_map(function (array $d): array {
                        $days = (int) ($d['days'] ?? 0);
                        $list = (float) ($d['price'] ?? 0);
                        $offer = (float) ($d['offer_price'] ?? 0);
                        $eff = self::planDurationEffectivePrice($d);
                        $d['effective_price'] = $eff;
                        $d['list_price'] = $list;
                        $d['has_offer'] = $offer > 0 && $offer < $list;
                        $d['price_per_day'] = $days > 0 ? round($eff / $days, 2) : 0.0;

                        return $d;
                    }, $rawDurations);
                }
            }
        }

        $durationMultipliers = self::DURATION_MULTIPLIERS;

        $locale = app()->getLocale();
        $selectedDurationIdFromCart = null;
        $selectedDurationLabel = null;
        if ($hasPlanItems && $firstPlanItem) {
            $selectedDurationIdFromCart = $firstPlanItem['options']['duration_id'] ?? null;
            if ($planDurations !== []) {
                if ($selectedDurationIdFromCart !== null && $selectedDurationIdFromCart !== '') {
                    $match = collect($planDurations)->first(function ($d) use ($selectedDurationIdFromCart) {
                        return (string) ($d['id'] ?? '') === (string) $selectedDurationIdFromCart;
                    });
                    if ($match) {
                        $selectedDurationLabel = is_array($match['label'] ?? null)
                            ? ($match['label'][$locale] ?? $match['label']['en'] ?? '')
                            : (string) ($match['label'] ?? '');
                    }
                }
                if ($selectedDurationLabel === null || $selectedDurationLabel === '') {
                    $days = (int) ($firstPlanItem['options']['duration_days'] ?? 0);
                    $matchByDays = collect($planDurations)->first(fn ($d) => (int) ($d['days'] ?? 0) === $days);
                    if ($matchByDays) {
                        $selectedDurationLabel = is_array($matchByDays['label'] ?? null)
                            ? ($matchByDays['label'][$locale] ?? $matchByDays['label']['en'] ?? '')
                            : (string) ($matchByDays['label'] ?? '');
                    }
                }
                if ($selectedDurationLabel === null || $selectedDurationLabel === '') {
                    $defaultDur = collect($planDurations)->first(fn ($d) => $d['is_default'] ?? false);
                    if ($defaultDur) {
                        $selectedDurationLabel = is_array($defaultDur['label'] ?? null)
                            ? ($defaultDur['label'][$locale] ?? $defaultDur['label']['en'] ?? '')
                            : (string) ($defaultDur['label'] ?? '');
                    }
                }
                if ($selectedDurationLabel === null || $selectedDurationLabel === '') {
                    $first = $planDurations[0] ?? null;
                    if ($first) {
                        $selectedDurationLabel = is_array($first['label'] ?? null)
                            ? ($first['label'][$locale] ?? $first['label']['en'] ?? '')
                            : (string) ($first['label'] ?? '');
                    }
                }
            }
            if ($selectedDurationLabel === null || $selectedDurationLabel === '') {
                $days = (int) ($firstPlanItem['options']['duration_days'] ?? 0);
                $selectedDurationLabel = $days > 0
                    ? $days.' '.__('days')
                    : (string) ($firstPlanItem['name'] ?? '');
            }
        }

        // Per-duration line prices (VAT-inclusive, matches meal-plan detail logic)
        $planDurationPrices = [];
        foreach ($planDurations as $d) {
            $id = (int) ($d['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $planDurationPrices[(string) $id] = (float) ($d['effective_price'] ?? self::planDurationEffectivePrice($d));
        }

        $preferredPlanDurationId = old('plan_duration_id', $selectedDurationIdFromCart);
        $durationIdChoices = array_map(fn ($d) => (string) ($d['id'] ?? ''), $planDurations);
        $durationIdChoices = array_values(array_filter($durationIdChoices, fn ($v) => $v !== ''));
        if ($planDurations !== [] && ($preferredPlanDurationId === null || $preferredPlanDurationId === '' || ! in_array((string) $preferredPlanDurationId, $durationIdChoices, true))) {
            $cartDays = (int) ($firstPlanItem['options']['duration_days'] ?? 0);
            $matchByDays = $cartDays > 0
                ? collect($planDurations)->first(fn ($d) => (int) ($d['days'] ?? 0) === $cartDays)
                : null;
            if ($matchByDays !== null && (string) ($matchByDays['id'] ?? '') !== '') {
                $preferredPlanDurationId = (string) $matchByDays['id'];
            } else {
                $defaultDur = collect($planDurations)->first(fn ($d) => $d['is_default'] ?? false) ?? ($planDurations[0] ?? null);
                $preferredPlanDurationId = $defaultDur ? (string) ($defaultDur['id'] ?? '') : '';
            }
        }

        $checkoutProgramId = $hasPlanItems && $firstPlanItem ? (int) ($firstPlanItem['id'] ?? 0) : 0;

        // When API returns no rows server-side, checkout JS can still fetch /api/plan/{id}/durations; this seeds one card from cart if needed.
        $cartDurationFallback = null;
        if ($hasPlanItems && $planDurations === [] && $firstPlanItem) {
            $days = (int) ($firstPlanItem['options']['duration_days'] ?? 0);
            $line = (float) ($firstPlanItem['price'] ?? 0);
            $durIdRaw = $firstPlanItem['options']['duration_id'] ?? null;
            $durId = ($durIdRaw !== null && $durIdRaw !== '') ? (int) $durIdRaw : 0;
            if ($days > 0 && $line > 0) {
                $cartDurationFallback = [
                    'id' => $durId,
                    'days' => $days,
                    'effective_price' => $line,
                    'price_per_day' => round($line / $days, 2),
                    'label' => $selectedDurationLabel ?? ($days.' '.__('days')),
                ];
            }
        }

        return view('pages.checkout', compact(
            'cart',
            'baseSubtotal',
            'deliveryFeeAmount',
            'vatRate',
            'zones',
            'durationMultipliers',
            'planDurations',
            'planDurationPrices',
            'selectedDurationLabel',
            'selectedDurationIdFromCart',
            'preferredPlanDurationId',
            'checkoutProgramId',
            'cartDurationFallback'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $cart = session()->get(CartManager::SESSION_SUBSCRIPTION)
            ?? session()->get(CartManager::SESSION_MARKET, []);

        if (empty($cart)) {
            return redirect()->route('meals.index')
                ->with('error', __('Your cart is empty'));
        }

        $hasPlanItems = collect($cart)->contains(fn ($item) => ! empty($item['options']['duration_days']));

        $validated = Validator::make($request->all(), [
            'start_date' => 'required|string|max:50',
            'duration' => 'required|in:once,weekly,monthly,3months',
            'delivery_type' => 'required|in:home,pickup',
            'coupon' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'branch_id' => 'required_if:delivery_type,pickup|nullable|integer',
            'zone_id' => 'required_if:delivery_type,home|nullable|integer',
            'street' => 'required_if:delivery_type,home|nullable|string|max:500',
            'building' => 'nullable|string|max:500',
            'delivery_pickup_type' => 'nullable|string|max:50',
        ])->validate();

        if ($hasPlanItems) {
            $validated['duration'] = 'once';
        }

        // Sync subscription plan line from selected API duration (price + options)
        if ($hasPlanItems) {
            $firstKey = null;
            foreach ($cart as $key => $item) {
                if (! empty($item['options']['duration_days'])) {
                    $firstKey = $key;
                    break;
                }
            }
            $programId = $firstKey !== null ? (int) ($cart[$firstKey]['id'] ?? 0) : 0;
            $planDurationsFromApi = $programId > 0 ? $this->externalDataService->getPlanDurations($programId) : [];

            if ($planDurationsFromApi !== []) {
                $requestedId = (int) $request->input('plan_duration_id', 0);
                $ids = array_map(fn ($d) => (int) ($d['id'] ?? 0), $planDurationsFromApi);
                if (! in_array($requestedId, $ids, true)) {
                    return redirect()->back()
                        ->withErrors(['plan_duration_id' => __('Please select a plan duration.')])
                        ->withInput();
                }
                $match = collect($planDurationsFromApi)->first(fn ($d) => (int) ($d['id'] ?? 0) === $requestedId);
                if ($match && $firstKey !== null) {
                    $linePrice = self::planDurationEffectivePrice($match);
                    $cart[$firstKey]['price'] = $linePrice;
                    $cart[$firstKey]['options']['duration_id'] = (string) ($match['id'] ?? $requestedId);
                    $cart[$firstKey]['options']['duration_days'] = (int) ($match['days'] ?? 0);
                    if (session()->has(CartManager::SESSION_SUBSCRIPTION)) {
                        session()->put(CartManager::SESSION_SUBSCRIPTION, $cart);
                    } else {
                        session()->put(CartManager::SESSION_MARKET, $cart);
                    }
                }
            }
        }

        // Calculate base subtotal
        $baseSubtotal = 0;
        foreach ($cart as $item) {
            $baseSubtotal += $item['price'] * $item['quantity'];
        }

        // Apply duration multiplier
        $multiplier = self::DURATION_MULTIPLIERS[$validated['duration']] ?? 1;
        $subtotal = round($baseSubtotal * $multiplier, 2);

        // Get delivery fee from zone if provided, otherwise from settings
        $deliveryFeeFromSettings = $hasPlanItems ? 0 : (float) Setting::getValue('delivery_fee', 25);
        $zoneDeliveryFee = 0.0;
        $zoneName = null;

        if ($validated['delivery_type'] === 'home' && ! empty($validated['zone_id'])) {
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
                $identifier = $validated['phone'];

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

        $pickupDescription = null;
        if (($validated['delivery_type'] ?? '') === 'pickup' && ! empty($validated['branch_id'])) {
            $branches = $this->externalDataService->getBranches();
            $br = collect($branches)->firstWhere('id', (int) $validated['branch_id']);
            if ($br) {
                $bn = $br['name'] ?? '';
                if (is_array($bn)) {
                    $bn = $bn[app()->getLocale()] ?? $bn['en'] ?? '';
                }
                $pickupDescription = trim(
                    __('Pickup branch').': '.$bn
                    .(! empty($br['address']) ? ' — '.$br['address'] : '')
                    .(! empty($br['phone']) ? ' — '.$br['phone'] : '')
                );
            }
        }

        $draftOrder = session('checkout_moyasar_order');
        $existingPayment = null;
        if (is_string($draftOrder) && $draftOrder !== '') {
            $existingPayment = Payment::query()
                ->where('order_number', $draftOrder)
                ->where('status', PaymentStatus::PENDING)
                ->first();
            if ($existingPayment && $existingPayment->isExpired()) {
                $existingPayment = null;
                session()->forget('checkout_moyasar_order');
            }
        }

        $buildingForPayment = $validated['delivery_type'] === 'home' ? ($validated['building'] ?? null) : null;
        if ($buildingForPayment !== null && $request->filled('delivery_pickup_type')) {
            $buildingForPayment = trim($buildingForPayment.' | pickup: '.$request->input('delivery_pickup_type'));
        }

        $paymentData = [
            'amount' => $amountInHalalas,
            'currency' => 'SAR',
            'subtotal' => (int) round($subtotal * 100),
            'delivery_fee' => (int) round($deliveryFee * 100),
            'vat_amount' => (int) round($vatAmount * 100),
            'discount_amount' => (int) round($discountAmount * 100),
            'customer_name' => $validated['name'],
            'customer_email' => null,
            'customer_phone' => $validated['phone'],
            'cart_items' => $cart,
            'start_date' => $validated['start_date'],
            'duration' => $validated['duration'],
            'delivery_type' => $validated['delivery_type'],
            'city' => $zoneName ?? ($validated['zone_id'] ?? null),
            'street' => $validated['delivery_type'] === 'home' ? ($validated['street'] ?? null) : null,
            'building' => $buildingForPayment,
            'description' => $pickupDescription,
            'coupon' => $couponCode,
            'expires_at' => now()->addMinutes(30),
        ];

        if ($existingPayment !== null) {
            $existingPayment->update($paymentData);
            $payment = $existingPayment;
        } else {
            $paymentData['order_number'] = Payment::generateOrderNumber();
            $paymentData['status'] = 'pending';
            $payment = Payment::create($paymentData);
        }

        session()->forget('checkout_moyasar_order');

        if ($coupon && $discountAmount > 0) {
            $coupon->incrementUsage($validated['phone']);
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

        if (! $coupon) {
            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => __('Invalid coupon code.'),
            ]);
        }

        if (! $coupon->isValid()) {
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

        if (! $coupon->isValidForUser($validated['identifier'])) {
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

    /**
     * POST delivery pin + unit details to external API /addresses (EXTERNAL_API_TOKEN).
     * Called from checkout after the user confirms the map address (and optional building row edits).
     */
    public function syncExternalAddress(Request $request): JsonResponse
    {
        try {
            $data = Validator::make($request->all(), [
                'phone' => 'required|string|max:20',
                'delivery_lat' => 'required|numeric',
                'delivery_lng' => 'required|numeric',
                'street' => 'nullable|string|max:1000',
                'delivery_description' => 'nullable|string|max:1000',
                'delivery_district_id' => 'required|integer|min:1',
                'delivery_type' => 'required|in:home,work,other',
                'delivery_pickup_type' => 'nullable|string|max:50',
                'delivery_title' => 'nullable|string|max:120',
                'building' => 'nullable|string|max:500',
            ])->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('payment.fill_delivery_first'),
                'errors' => $e->errors(),
            ], 422);
        }

        $baseDesc = trim((string) ($data['street'] ?? ''));
        if ($baseDesc === '' && ! empty($data['delivery_description'])) {
            $baseDesc = trim((string) $data['delivery_description']);
        }

        $buildingExtra = trim((string) ($data['building'] ?? ''));
        $fullDesc = $baseDesc;
        if ($buildingExtra !== '') {
            $fullDesc = $baseDesc === '' ? $buildingExtra : $baseDesc."\n".$buildingExtra;
        }

        if ($fullDesc === '') {
            return response()->json(['success' => false, 'message' => __('Please enter address details')], 422);
        }

        $title = match ($data['delivery_type']) {
            'work' => 'Office',
            'other' => trim((string) ($data['delivery_title'] ?? '')) !== ''
                ? trim((string) $data['delivery_title'])
                : 'Other',
            default => 'Home',
        };

        $apiType = match ($data['delivery_type']) {
            'work' => 'commercial',
            'other' => 'other',
            default => 'residential',
        };

        $pickup = (string) ($data['delivery_pickup_type'] ?? 'hand_it_to_me');
        if (! in_array($pickup, ['hand_it_to_me', 'leave_at_door'], true)) {
            $pickup = 'hand_it_to_me';
        }

        $payload = [
            'title' => $title,
            'latitude' => (string) $data['delivery_lat'],
            'longitude' => (string) $data['delivery_lng'],
            'description' => $fullDesc,
            'type' => $apiType,
            'district_id' => (string) $data['delivery_district_id'],
            'pickup_type' => $pickup,
        ];

        $userToken = session('external_api_token');
        if (is_string($userToken) && $userToken !== '') {
            $result = app(ApiAuthService::class)->storeAddress($userToken, $payload);
        } else {
            $result = $this->externalDataService->createAddress($payload);
        }

        if (($result['skipped'] ?? false) === true) {
            return response()->json(['success' => true, 'skipped' => true]);
        }

        $httpOk = (bool) ($result['_http_ok'] ?? false);
        $apiStatus = (int) ($result['status'] ?? 0);
        $hasData = array_key_exists('data', $result);

        if ($httpOk && ($apiStatus === 200 || $hasData)) {
            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => (string) ($result['message'] ?? __('address.save_failed')),
        ], 422);
    }

    /**
     * Hydrate saved addresses + profile after page reload (session token from external login).
     */
    public function customerState(): JsonResponse
    {
        $token = session('external_api_token');
        if (! is_string($token) || $token === '') {
            return response()->json([
                'success' => false,
                'addresses' => [],
                'profile' => [],
            ]);
        }

        $addresses = app(ApiAuthService::class)->getAddresses($token, true);
        if (! is_array($addresses)) {
            $addresses = [];
        }

        $profile = session('external_api_profile', []);
        if (! is_array($profile)) {
            $profile = [];
        }

        return response()->json([
            'success' => true,
            'addresses' => $addresses,
            'profile' => $profile,
            'is_continue' => (bool) session('external_login_is_continue', false),
        ]);
    }

    /**
     * Create or update a pending payment and return Moyasar bootstrap data (after phone OTP verification).
     */
    public function moyasarSession(Request $request, MoyasarPaymentService $moyasarService): JsonResponse
    {
        $verified = session('phone_verified');
        $phone = (string) $request->input('phone', '');
        $previewOnly = filter_var($request->input('preview_only', false), FILTER_VALIDATE_BOOLEAN);
        $phoneNorm = str_replace(' ', '', $phone);
        $verifiedNorm = $verified ? str_replace(' ', '', (string) $verified) : '';
        $phonesMatch = $verifiedNorm !== '' && $verifiedNorm === $phoneNorm;

        if (! $phonesMatch) {
            if (! $previewOnly) {
                return response()->json(['success' => false, 'message' => __('Unauthorized')], 403);
            }
        } else {
            $previewOnly = false;
        }

        $cart = session()->get(CartManager::SESSION_SUBSCRIPTION)
            ?? session()->get(CartManager::SESSION_MARKET, []);

        if ($cart === []) {
            return response()->json(['success' => false, 'message' => __('Your cart is empty')], 422);
        }

        $hasPlanItems = collect($cart)->contains(fn ($item) => ! empty($item['options']['duration_days']));

        $rules = [
            'phone' => 'required|string|max:20',
            'start_date' => 'required|string|max:50',
            'duration' => 'required|in:once,weekly,monthly,3months',
            'delivery_type' => 'required|in:home,pickup',
            'coupon' => 'nullable|string|max:50',
            'branch_id' => 'required_if:delivery_type,pickup|nullable|integer',
            'zone_id' => 'required_if:delivery_type,home|nullable|integer',
            'selected_address_id' => 'nullable|integer',
        ];
        if ($hasPlanItems) {
            $rules['plan_duration_id'] = 'required|integer';
        } else {
            $rules['plan_duration_id'] = 'nullable|integer';
        }

        try {
            $validated = Validator::make($request->all(), $rules)->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('payment.fill_delivery_first'),
                'errors' => $e->errors(),
            ], 422);
        }

        if (
            ($validated['delivery_type'] ?? '') === 'home'
            && empty($validated['zone_id'])
            && empty($validated['selected_address_id'])
        ) {
            return response()->json([
                'success' => false,
                'message' => __('payment.fill_delivery_first'),
                'errors' => ['zone_id' => [__('Please select a city or a saved address.')]],
            ], 422);
        }

        if ($hasPlanItems) {
            $validated['duration'] = 'once';
        }

        if ($hasPlanItems) {
            $firstKey = null;
            foreach ($cart as $key => $item) {
                if (! empty($item['options']['duration_days'])) {
                    $firstKey = $key;
                    break;
                }
            }
            $programId = $firstKey !== null ? (int) ($cart[$firstKey]['id'] ?? 0) : 0;
            $planDurationsFromApi = $programId > 0 ? $this->externalDataService->getPlanDurations($programId) : [];

            if ($planDurationsFromApi !== []) {
                $requestedId = (int) $request->input('plan_duration_id', 0);
                $ids = array_map(fn ($d) => (int) ($d['id'] ?? 0), $planDurationsFromApi);
                if (! in_array($requestedId, $ids, true)) {
                    return response()->json(['success' => false, 'message' => __('Please select a plan duration.')], 422);
                }
                $match = collect($planDurationsFromApi)->first(fn ($d) => (int) ($d['id'] ?? 0) === $requestedId);
                if ($match && $firstKey !== null) {
                    $linePrice = self::planDurationEffectivePrice($match);
                    $cart[$firstKey]['price'] = $linePrice;
                    $cart[$firstKey]['options']['duration_id'] = (string) ($match['id'] ?? $requestedId);
                    $cart[$firstKey]['options']['duration_days'] = (int) ($match['days'] ?? 0);
                    if (session()->has(CartManager::SESSION_SUBSCRIPTION)) {
                        session()->put(CartManager::SESSION_SUBSCRIPTION, $cart);
                    } else {
                        session()->put(CartManager::SESSION_MARKET, $cart);
                    }
                }
            }
        }

        $baseSubtotal = 0;
        foreach ($cart as $item) {
            $baseSubtotal += $item['price'] * $item['quantity'];
        }

        $multiplier = self::DURATION_MULTIPLIERS[$validated['duration']] ?? 1;
        $subtotal = round($baseSubtotal * $multiplier, 2);

        $deliveryFeeFromSettings = $hasPlanItems ? 0 : (float) Setting::getValue('delivery_fee', 25);
        $zoneDeliveryFee = 0.0;
        $zoneName = null;

        if ($validated['delivery_type'] === 'home' && ! empty($validated['zone_id'])) {
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

        $discountAmount = 0.0;
        $coupon = null;
        $couponCode = $validated['coupon'] ?? null;

        if ($couponCode) {
            $coupon = Coupon::where('code', strtoupper($couponCode))->first();

            if ($coupon && $coupon->isValidForUser($phone)) {
                $discountAmount = $coupon->calculateDiscount($subtotal);
            }
        }

        $vatRate = (float) Setting::getValue('vat_rate', 15) / 100;
        $total = $subtotal + $deliveryFee - $discountAmount;
        $vatAmount = round($total - ($total / (1 + $vatRate)), 2);
        $amountInHalalas = (int) round($total * 100);

        $pickupDescription = null;
        if (($validated['delivery_type'] ?? '') === 'pickup' && ! empty($validated['branch_id'])) {
            $branches = $this->externalDataService->getBranches();
            $br = collect($branches)->firstWhere('id', (int) $validated['branch_id']);
            if ($br) {
                $bn = $br['name'] ?? '';
                if (is_array($bn)) {
                    $bn = $bn[app()->getLocale()] ?? $bn['en'] ?? '';
                }
                $pickupDescription = trim(
                    __('Pickup branch').': '.$bn
                    .(! empty($br['address']) ? ' — '.$br['address'] : '')
                    .(! empty($br['phone']) ? ' — '.$br['phone'] : '')
                );
            }
        }

        if ($previewOnly) {
            return response()->json([
                'success' => true,
                'preview' => true,
                'amount_halalas' => $amountInHalalas,
                'publishable_key' => $moyasarService->getPublishableKey(),
                'callback_url' => route('checkout.moyasar-preview-callback'),
                'currency' => 'SAR',
                'description' => __('payment.preview_checkout_description'),
            ]);
        }

        $draftOrder = session('checkout_moyasar_order');
        $existingPayment = null;
        if (is_string($draftOrder) && $draftOrder !== '') {
            $existingPayment = Payment::query()
                ->where('order_number', $draftOrder)
                ->where('status', PaymentStatus::PENDING)
                ->first();
            if ($existingPayment && $existingPayment->isExpired()) {
                $existingPayment = null;
                session()->forget('checkout_moyasar_order');
            }
        }

        $paymentData = [
            'amount' => $amountInHalalas,
            'currency' => 'SAR',
            'subtotal' => (int) round($subtotal * 100),
            'delivery_fee' => (int) round($deliveryFee * 100),
            'vat_amount' => (int) round($vatAmount * 100),
            'discount_amount' => (int) round($discountAmount * 100),
            'customer_name' => '—',
            'customer_email' => null,
            'customer_phone' => $phone,
            'cart_items' => $cart,
            'start_date' => $validated['start_date'],
            'duration' => $validated['duration'],
            'delivery_type' => $validated['delivery_type'],
            'city' => $zoneName ?? ($validated['zone_id'] ?? null),
            'street' => null,
            'building' => null,
            'description' => $pickupDescription,
            'coupon' => $couponCode,
            'expires_at' => now()->addMinutes(30),
        ];

        if ($existingPayment !== null) {
            $existingPayment->update($paymentData);
            $payment = $existingPayment;
        } else {
            $paymentData['order_number'] = Payment::generateOrderNumber();
            $paymentData['status'] = 'pending';
            $payment = Payment::create($paymentData);
            session(['checkout_moyasar_order' => $payment->order_number]);
        }

        return response()->json([
            'success' => true,
            'preview' => false,
            'order_number' => $payment->order_number,
            'amount_halalas' => $payment->amount,
            'publishable_key' => $moyasarService->getPublishableKey(),
            'callback_url' => route('payment.callback'),
            'currency' => $payment->currency,
            'description' => __('payment.description', [
                'order' => $payment->order_number,
            ]),
        ]);
    }

    /**
     * Moyasar redirects here only for the pre-verification checkout preview widget.
     * Real payments use {@see PaymentController::callback} with a stored order number.
     */
    public function moyasarPreviewCallback(Request $request): RedirectResponse
    {
        Log::warning('Checkout Moyasar preview callback invoked (no order recorded)', [
            'query' => $request->query(),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('checkout.index')
            ->with('error', __('payment.verify_phone_before_payment'));
    }

    /**
     * VAT-inclusive line price for a duration row (offer_price when lower).
     */
    private static function planDurationEffectivePrice(array $d): float
    {
        $p = (float) ($d['price'] ?? 0);
        $o = (float) ($d['offer_price'] ?? 0);

        return ($o > 0 && $o < $p) ? $o : $p;
    }
}
