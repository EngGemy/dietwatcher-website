<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentKind;
use App\Enums\PaymentStatus;
use App\Livewire\Cart\CartManager;
use App\Models\Payment;
use App\Models\Settings\Setting;
use App\Services\AccountApiService;
use App\Services\ApiAuthService;
use App\Services\ExternalDataService;
use App\Services\Payment\MoyasarPaymentService;
use App\Support\AddressCheckoutHelper;
use App\Support\SaudiPhone;
use App\Support\SubscriptionCheckoutPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const DURATION_MULTIPLIERS = [
        'once' => 1,
        'weekly' => 0.25,
        'monthly' => 1,
        '3months' => 3,
    ];

    private const CHECKOUT_REGION_DURATION_SESSION = 'checkout_region_duration_by_address';

    private static function normalizePhoneForMatch(?string $phone): string
    {
        return SaudiPhone::matchKey($phone);
    }

    /**
     * All saved addresses the customer can view/delete during checkout.
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkoutManageableAddresses(string $token): array
    {
        $addresses = app(ApiAuthService::class)->getAddresses($token, true, false);
        if (! is_array($addresses)) {
            $addresses = [];
        }

        return AddressCheckoutHelper::markDeliverability($addresses);
    }

    /**
     * Active saved addresses for checkout UI (excludes soft-deleted rows).
     *
     * @return array<int, array<string, mixed>>
     */
    private function checkoutActiveAddresses(string $token): array
    {
        $addresses = array_values(array_filter(
            $this->checkoutManageableAddresses($token),
            static fn (array $row): bool => ApiAuthService::isAddressRowActive($row)
        ));

        return AddressCheckoutHelper::markDeliverability($addresses);
    }

    private function rememberCheckoutRegionDuration(int $addressId, int $regionDurationId): void
    {
        if ($addressId <= 0 || $regionDurationId <= 0) {
            return;
        }

        $map = session(self::CHECKOUT_REGION_DURATION_SESSION, []);
        if (! is_array($map)) {
            $map = [];
        }
        $map[(string) $addressId] = $regionDurationId;
        session([self::CHECKOUT_REGION_DURATION_SESSION => $map]);
    }

    private function checkoutRegionDurationId(int $addressId): int
    {
        if ($addressId <= 0) {
            return 0;
        }

        $map = session(self::CHECKOUT_REGION_DURATION_SESSION, []);
        if (! is_array($map)) {
            return 0;
        }

        return (int) ($map[(string) $addressId] ?? 0);
    }

    /**
     * GET /checkout/district-durations — delivery time slots for a district (same data as mobile app).
     */
    public function districtDeliveryTimes(Request $request): JsonResponse
    {
        $token = session('external_api_token');
        if (! is_string($token) || $token === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.verify_phone_to_pay'),
                'durations' => [],
            ], 403);
        }

        $districtId = (int) $request->query('district_id', 0);
        $addressId = (int) $request->query('address_id', 0);
        if ($districtId <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.select_delivery_time'),
                'durations' => [],
            ], 422);
        }

        $auth = app(ApiAuthService::class);
        $durations = $auth->findDistrictRegionDurations($token, $districtId, $addressId > 0 ? $addressId : null);

        return response()->json([
            'success' => true,
            'durations' => $durations,
        ]);
    }

    public function __construct(
        private ExternalDataService $externalDataService,
        private AccountApiService $accountApiService,
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

            // Resolve duration days + id from authoritative API list (not nested profile ids)
            $apiDurations = $this->externalDataService->getCheckoutPlanDurations($planId);
            $resolvedDurationDays = (int) ($plan->duration_days ?? 28);
            $resolvedDurationId = $durationId;
            $resolvedDurationIdInt = SubscriptionCheckoutPayload::resolvePlanDurationId(
                $planId,
                (int) $durationId,
                $durationDaysParam,
                $this->externalDataService,
            );
            if ($resolvedDurationIdInt > 0) {
                $resolvedDurationId = (string) $resolvedDurationIdInt;
                $byId = collect($apiDurations)->first(fn (array $d) => (int) ($d['id'] ?? 0) === $resolvedDurationIdInt);
                if ($byId !== null) {
                    $resolvedDurationDays = (int) ($byId['days'] ?? $resolvedDurationDays);
                }
            } elseif ($durationDaysParam > 0) {
                $resolvedDurationDays = $durationDaysParam;
                $resolvedDurationId = '';
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

            $calorieId = (int) $request->get('calorie_id', 0);
            if ($calorieId <= 0 && $calories !== '') {
                $calorieId = SubscriptionCheckoutPayload::resolvePlanCaloryId(
                    $planId,
                    $subscriptionPlanId,
                    (string) $calories,
                    $this->externalDataService
                );
            }

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
                        'calorie_id' => $calorieId > 0 ? $calorieId : null,
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
                    $rawDurations = $this->externalDataService->getCheckoutPlanDurations($programId);
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

        $withWeekend = $hasPlanItems
            ? SubscriptionCheckoutPayload::resolveWithWeekend($cart, $this->externalDataService)
            : '0';

        $minStartDate = '';
        $defaultStartDate = '';
        $scheduleReady = false;

        $customerToken = session('external_api_token');
        if ($hasPlanItems && is_string($customerToken) && $customerToken !== '') {
            $schedulePayload = SubscriptionCheckoutPayload::buildMinimalSchedulePayload(
                $cart,
                $this->externalDataService,
                [
                    'plan_duration_id' => (int) ($preferredPlanDurationId ?? 0),
                    'delivery_type' => old('delivery_type', 'home'),
                    'with_weekend' => $withWeekend,
                ],
            );
            if ($schedulePayload !== []) {
                $calc = $this->accountApiService->calculateSubscription($schedulePayload, $customerToken);
                if ($calc['ok'] ?? false) {
                    $calcData = is_array($calc['data'] ?? null) ? $calc['data'] : null;
                    $apiMin = SubscriptionCheckoutPayload::extractApiMinStartDate($calcData);
                    if ($apiMin !== '') {
                        $minStartDate = $apiMin;
                        $defaultStartDate = $apiMin;
                        $scheduleReady = true;
                    } elseif (is_array($calcData)) {
                        foreach ([
                            'first_available_date_for_subscription',
                            'min_start_date',
                            'first_available_date',
                        ] as $key) {
                            $raw = trim((string) ($calcData[$key] ?? ''));
                            if ($raw !== '') {
                                Log::warning('CheckoutController::index rejected API subscription start date', [
                                    'raw' => $raw,
                                    'field' => $key,
                                ]);
                                break;
                            }
                        }
                    }
                }
            }
        }

        if ($hasPlanItems && $minStartDate === '') {
            $withWeekendBool = $withWeekend === '1';
            $minStartDate = SubscriptionCheckoutPayload::computeFallbackMinStartDate($withWeekendBool);
            $defaultStartDate = $minStartDate;
        }

        if ($scheduleReady) {
            $normalizedOld = SubscriptionCheckoutPayload::normalizeStartDate((string) old('start_date', ''));
            if ($normalizedOld !== '' && $normalizedOld >= $minStartDate) {
                $defaultStartDate = $normalizedOld;
            }
        }

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
            'cartDurationFallback',
            'defaultStartDate',
            'minStartDate',
            'withWeekend',
            'scheduleReady',
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
            'start_date' => $hasPlanItems ? 'required|string|max:50' : 'nullable|string|max:50',
            'duration' => $hasPlanItems ? 'required|in:once,weekly,monthly,3months' : 'nullable|in:once,weekly,monthly,3months',
            'delivery_type' => 'required|in:home,pickup',
            'coupon' => 'nullable|string|max:50',
            'promocode_name' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:25',
            'branch_id' => 'required_if:delivery_type,pickup|nullable|integer',
            // Saved-address flow may provide selected_address_id only.
            'zone_id' => 'nullable|integer',
            'selected_address_id' => 'nullable|integer',
            'street' => 'required_if:delivery_type,home|nullable|string|max:500',
            'building' => 'nullable|string|max:500',
            'delivery_pickup_type' => 'nullable|string|max:50',
        ])->validate();

        $normalizedPhone = SaudiPhone::to966((string) ($validated['phone'] ?? ''));
        if ($normalizedPhone === '') {
            return redirect()->back()
                ->withErrors(['phone' => __('checkout.phone_saudi_invalid')])
                ->withInput();
        }
        $validated['phone'] = $normalizedPhone;

        if (
            ($validated['delivery_type'] ?? '') === 'home'
            && empty($validated['zone_id'])
            && ! empty($validated['selected_address_id'])
        ) {
            $token = session('external_api_token');
            if (is_string($token) && $token !== '') {
                $savedAddresses = $this->checkoutActiveAddresses($token);
                $picked = collect($savedAddresses)->first(
                    fn ($a) => (int) ($a['id'] ?? 0) === (int) $validated['selected_address_id']
                );
                if (is_array($picked)) {
                    $resolvedZoneId = $picked['city']['id']
                        ?? $picked['city_id']
                        ?? $picked['zone_id']
                        ?? $picked['zone']['id']
                        ?? $picked['district']['city_id']
                        ?? $picked['district']['zone_id']
                        ?? $picked['district']['city']['id']
                        ?? $picked['district']['zone']['id']
                        ?? null;
                    if ($resolvedZoneId) {
                        $validated['zone_id'] = (int) $resolvedZoneId;
                    }
                }
            }
        }

        if (
            ($validated['delivery_type'] ?? '') === 'home'
            && empty($validated['zone_id'])
        ) {
            return redirect()->back()
                ->withErrors(['zone_id' => __('Please select a city or a saved address.')])
                ->withInput();
        }

        $validated['duration'] = 'once';

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
            $planDurationsFromApi = $programId > 0 ? $this->externalDataService->getCheckoutPlanDurations($programId) : [];
            $cartDurationDays = $firstKey !== null ? (int) ($cart[$firstKey]['options']['duration_days'] ?? 0) : 0;
            $requestedId = (int) $request->input('plan_duration_id', 0);
            if ($requestedId > 0) {
                $selectedRow = collect($planDurationsFromApi)->first(
                    fn ($d) => (int) ($d['id'] ?? 0) === $requestedId
                );
                if ($selectedRow) {
                    $cartDurationDays = (int) ($selectedRow['days'] ?? $cartDurationDays);
                }
            }
            $resolvedDurationId = SubscriptionCheckoutPayload::resolvePlanDurationId(
                $programId,
                $requestedId,
                $cartDurationDays,
                $this->externalDataService,
            );
            if ($planDurationsFromApi !== [] && $resolvedDurationId <= 0) {
                return redirect()->back()
                    ->withErrors(['plan_duration_id' => __('checkout.invalid_plan_duration')])
                    ->withInput();
            }
            if ($resolvedDurationId > 0) {
                $match = collect($planDurationsFromApi)->first(fn ($d) => (int) ($d['id'] ?? 0) === $resolvedDurationId);
                if ($match && $firstKey !== null) {
                    $linePrice = self::planDurationEffectivePrice($match);
                    $cart[$firstKey]['price'] = $linePrice;
                    $cart[$firstKey]['options']['duration_id'] = (string) ($match['id'] ?? $resolvedDurationId);
                    $cart[$firstKey]['options']['duration_days'] = (int) ($match['days'] ?? 0);
                    if (session()->has(CartManager::SESSION_SUBSCRIPTION)) {
                        session()->put(CartManager::SESSION_SUBSCRIPTION, $cart);
                    } else {
                        session()->put(CartManager::SESSION_MARKET, $cart);
                    }
                }
                $request->merge(['plan_duration_id' => $resolvedDurationId]);
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

        // Promo discounts are resolved via external /subscriptions/calculate in resolvePaymentAmounts().
        $discountAmount = 0.0;
        $couponCode = $validated['promocode_name'] ?? ($validated['coupon'] ?? null);

        $vatRate = (float) Setting::getValue('vat_rate', 15) / 100;
        $isSubscriptionCheckout = $hasPlanItems && session()->has(CartManager::SESSION_SUBSCRIPTION);
        $planDurationId = (int) $request->input('plan_duration_id', 0);
        $amounts = $this->resolvePaymentAmounts(
            $isSubscriptionCheckout,
            $cart,
            $validated,
            $planDurationId,
            $subtotal,
            $deliveryFee,
            $discountAmount,
            $vatRate,
        );
        $subtotal = $amounts['subtotal'];
        $deliveryFee = $amounts['delivery'];
        $discountAmount = $amounts['discount'];
        $vatAmount = $amounts['vat'];
        $total = $amounts['total'];
        $amountInHalalas = (int) round($total * 100);
        $subscriptionApiPayload = $amounts['subscription_api_payload'];

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

        $checkoutPayload = [
            'delivery_type' => $validated['delivery_type'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'selected_address_id' => $validated['selected_address_id'] ?? null,
            'zone_id' => $validated['zone_id'] ?? null,
            'note' => (string) ($request->input('note', '') ?? ''),
            'payment_option' => 'credit_card',
            'use_wallet' => (string) ($request->input('useWallet', '0') ?? '0'),
            'plan_duration_id' => $planDurationId > 0 ? $planDurationId : null,
        ];
        if ($subscriptionApiPayload !== []) {
            $checkoutPayload['subscription_api'] = $subscriptionApiPayload;
        }

        $paymentData = [
            'kind' => $isSubscriptionCheckout ? PaymentKind::Subscription : PaymentKind::Order,
            'amount' => $amountInHalalas,
            'currency' => 'SAR',
            'subtotal' => (int) round($subtotal * 100),
            'delivery_fee' => (int) round($deliveryFee * 100),
            'vat_amount' => (int) round($vatAmount * 100),
            'discount_amount' => (int) round($discountAmount * 100),
            'customer_name' => $validated['name'],
            'customer_email' => null,
            'customer_phone' => $validated['phone'],
            'customer_phone_normalized' => self::normalizePhoneForMatch((string) $validated['phone']),
            'cart_items' => $cart,
            'checkout_payload' => $checkoutPayload,
            'start_date' => $validated['start_date'] ?? now()->format('Y-m-d'),
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
            'identifier' => 'nullable|string|max:255',
            'program_id' => 'nullable|integer',
            'subscription_plan_id' => 'nullable|integer',
            'plan_duration_id' => 'nullable|integer',
            'plan_calory_id' => 'nullable|integer',
            'delivery_type' => 'nullable|in:home,pickup',
            'start_date' => 'nullable|string|max:20',
            'selected_address_id' => 'nullable|string|max:50',
            'region_duration_id' => 'nullable|string|max:50',
            'branch_id' => 'nullable|string|max:50',
            'zone_id' => 'nullable|string|max:50',
        ]);

        $rawIdentifier = trim((string) ($validated['identifier'] ?? ''));
        if ($rawIdentifier !== '') {
            $identifier = SaudiPhone::to966($rawIdentifier);
            if ($identifier === '') {
                return response()->json([
                    'valid' => false,
                    'discount' => 0,
                    'message' => __('checkout.phone_saudi_invalid'),
                ], 422);
            }
            $validated['identifier'] = $identifier;
        } else {
            $validated['identifier'] = '';
        }

        $customerToken = (string) session('external_api_token', '');
        if ($customerToken === '') {
            $pendingRegister = trim((string) session('pending_register_mobile', '')) !== '';

            return response()->json([
                'valid' => false,
                'discount' => 0,
                'message' => $pendingRegister
                    ? __('checkout.promo_requires_registration')
                    : __('checkout.promo_requires_verified_phone'),
                'source' => 'external',
            ], 422);
        }

        $programId = (int) ($validated['program_id'] ?? 0);
        if ($programId > 0) {
            $planDurationId = (int) ($validated['plan_duration_id'] ?? 0);
            if ($planDurationId <= 0) {
                return response()->json([
                    'valid' => false,
                    'discount' => 0,
                    'message' => __('checkout.promo_select_duration'),
                ], 422);
            }

            $extResult = $this->validatePromoViaExternalApi(
                (string) $validated['code'],
                $validated,
            );

            return response()->json($extResult, ($extResult['valid'] ?? false) ? 200 : 422);
        }

        return response()->json([
            'valid' => false,
            'discount' => 0,
            'message' => __('checkout.promo_store_not_supported'),
            'source' => 'external',
        ], 422);
    }

    /**
     * Validate promo via POST /subscriptions/calculate (customer token required).
     *
     * @param  array<string, mixed>  $checkoutContext
     * @return array<string, mixed>
     */
    private function validatePromoViaExternalApi(string $code, array $checkoutContext): array
    {
        if (! filled(config('services.external_api.url'))) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => __('checkout.promo_unavailable'),
                'source' => 'external',
            ];
        }

        $token = (string) session('external_api_token', '');

        try {
            $cart = session()->get(CartManager::SESSION_SUBSCRIPTION, []);
            $planDurationId = (int) ($checkoutContext['plan_duration_id'] ?? 0);
            $codeVariants = $this->promoCodeVariants($code);
            $lastFailure = [
                'valid' => false,
                'discount' => 0,
                'message' => (string) __('checkout.promo_invalid'),
                'source' => 'external',
            ];

            foreach ($codeVariants as $promoCode) {
                $result = $this->attemptExternalPromoValidation(
                    $promoCode,
                    $checkoutContext,
                    $cart,
                    $token,
                    $planDurationId,
                );

                if ($result['valid'] ?? false) {
                    return $result;
                }

                $lastFailure = $result;
                if ($result['api_rejected'] ?? false) {
                    continue;
                }
            }

            return $lastFailure;
        } catch (\Exception $e) {
            Log::warning('External promo validation failed', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => false,
                'discount' => 0,
                'message' => __('checkout.promo_unavailable'),
                'source' => 'external',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $checkoutContext
     * @param  array<string, mixed>  $cart
     * @return array<string, mixed>
     */
    private function attemptExternalPromoValidation(
        string $promoCode,
        array $checkoutContext,
        array $cart,
        string $token,
        int $planDurationId,
    ): array {
        $validated = array_merge($checkoutContext, [
            'promocode_name' => $promoCode,
            'coupon' => $promoCode,
            'delivery_type' => (string) ($checkoutContext['delivery_type'] ?? 'home'),
        ]);

        $payload = SubscriptionCheckoutPayload::buildFormPayload(
            $validated,
            $cart,
            $this->externalDataService,
            $planDurationId > 0 ? $planDurationId : null,
        );

        if ($payload === []) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => __('checkout.promo_select_duration'),
                'source' => 'external',
                'api_rejected' => false,
            ];
        }

        $payloadWithPromo = $payload;
        $payloadBaseline = $payload;
        unset($payloadBaseline['promocode_name']);

        $withPromo = $this->accountApiService->calculateSubscription($payloadWithPromo, $token);
        if (! ($withPromo['ok'] ?? false)) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => $this->extractExternalPromoErrorMessage($withPromo),
                'source' => 'external',
                'api_rejected' => $this->externalPromoWasRejected($withPromo),
            ];
        }

        $promoData = is_array($withPromo['data'] ?? null) ? $withPromo['data'] : [];
        $baselineData = null;
        $baseline = $this->accountApiService->calculateSubscription($payloadBaseline, $token);
        if ($baseline['ok'] ?? false) {
            $baselineData = is_array($baseline['data'] ?? null) ? $baseline['data'] : [];
        }

        $requestSubtotal = (float) ($checkoutContext['subtotal'] ?? 0);
        $discount = $this->extractDiscountFromCalculateResponse($promoData, $baselineData, $requestSubtotal);
        $apiTotal = $this->extractCalculatePayableTotal($promoData);

        if ($discount <= 0 && $apiTotal >= 0 && $baselineData !== null) {
            $baselineTotal = $this->extractCalculatePayableTotal($baselineData);
            if ($baselineTotal > 0 && $apiTotal < $baselineTotal) {
                $discount = $baselineTotal - $apiTotal;
            }
        }

        if ($discount <= 0) {
            return [
                'valid' => false,
                'discount' => 0,
                'message' => (string) __('checkout.promo_invalid'),
                'source' => 'external',
                'api_rejected' => false,
            ];
        }

        return [
            'valid' => true,
            'discount' => round(max(0, $discount), 2),
            'api_total' => $apiTotal >= 0 ? round($apiTotal, 2) : null,
            'message' => __('checkout.promo_applied_success'),
            'source' => 'external',
            'type' => 'fixed',
            'value' => round(max(0, $discount), 2),
            'promocode_name' => $promoCode,
        ];
    }

    /**
     * @return list<string>
     */
    private function promoCodeVariants(string $code): array
    {
        $trimmed = trim($code);
        if ($trimmed === '') {
            return [];
        }

        $variants = [
            $trimmed,
            strtoupper($trimmed),
            strtolower($trimmed),
            ucfirst(strtolower($trimmed)),
        ];

        return array_values(array_unique(array_filter($variants, static fn (string $value): bool => $value !== '')));
    }

    /**
     * @param  array<string, mixed>|null  $baselineData
     */
    private function extractDiscountFromCalculateResponse(
        array $promoData,
        ?array $baselineData,
        float $requestSubtotal = 0,
    ): float {
        $promoSource = $this->accountApiService->resolveCalculatePricingSource($promoData);

        if ($baselineData !== null) {
            $baselineTotal = $this->extractCalculatePayableTotal($baselineData);
            $promoTotal = $this->extractCalculatePayableTotal($promoData);
            if ($baselineTotal >= 0 && $promoTotal >= 0 && $promoTotal < $baselineTotal) {
                return $baselineTotal - $promoTotal;
            }
        }

        $parsed = $this->accountApiService->parseSubscriptionCalculateTotals($promoData);
        if (is_array($parsed) && (float) ($parsed['discount'] ?? 0) > 0) {
            return (float) $parsed['discount'];
        }

        foreach (['discount', 'discount_amount', 'promo_discount', 'coupon_discount', 'promocode_discount'] as $key) {
            $amount = $this->externalMoneyAmount(data_get($promoSource, $key));
            if ($amount > 0) {
                return $amount;
            }
        }

        $orderTotal = $this->externalMoneyAmount($promoSource['order_total'] ?? 0);
        $afterDiscount = $this->externalMoneyAmount($promoSource['after_discount'] ?? 0);
        if ($orderTotal > 0 && $afterDiscount >= 0 && $afterDiscount < $orderTotal) {
            return $orderTotal - $afterDiscount;
        }

        $subtotal = $this->externalMoneyAmount(
            $promoSource['order_total'] ?? $promoSource['subtotal'] ?? $promoSource['price'] ?? $promoSource['plan_price'] ?? 0
        );
        $total = $this->externalMoneyAmount(
            $promoSource['total'] ?? $promoSource['after_discount'] ?? $promoSource['grand_total'] ?? $promoSource['amount'] ?? $promoSource['final_total'] ?? -1
        );

        if ($subtotal > 0 && $total >= 0 && $total < $subtotal) {
            return $subtotal - $total;
        }

        if ($subtotal > 0 && $total === 0.0) {
            return $subtotal;
        }

        if ($requestSubtotal > 0 && $total === 0.0) {
            return $requestSubtotal;
        }

        return 0.0;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function externalPromoWasRejected(array $result): bool
    {
        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : [];
        $errors = $raw['errors'] ?? null;
        if (! is_array($errors)) {
            return false;
        }

        foreach (['promocode_name', 'promocode', 'promo_code', 'coupon', 'promo'] as $field) {
            if (! empty($errors[$field])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function extractExternalPromoErrorMessage(array $result): string
    {
        $message = trim((string) ($result['message'] ?? ''));
        if ($message !== '') {
            return $message;
        }

        $raw = is_array($result['raw'] ?? null) ? $result['raw'] : [];
        $errors = $raw['errors'] ?? null;
        if (is_array($errors)) {
            foreach (['promocode_name', 'promocode', 'promo_code', 'coupon'] as $field) {
                $fieldErrors = $errors[$field] ?? null;
                if (is_array($fieldErrors) && isset($fieldErrors[0])) {
                    return trim((string) $fieldErrors[0]);
                }
            }

            foreach ($errors as $fieldErrors) {
                if (is_array($fieldErrors) && isset($fieldErrors[0])) {
                    return trim((string) $fieldErrors[0]);
                }
                if (is_string($fieldErrors) && trim($fieldErrors) !== '') {
                    return trim($fieldErrors);
                }
            }
        }

        return (string) __('checkout.promo_invalid');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractCalculatePayableTotal(array $data): float
    {
        $parsed = $this->accountApiService->parseSubscriptionCalculateTotals($data);
        if (is_array($parsed)) {
            return (float) ($parsed['total'] ?? -1);
        }

        $source = $this->accountApiService->resolveCalculatePricingSource($data);

        return $this->externalMoneyAmount(
            $source['total'] ?? $source['after_discount'] ?? $source['grand_total'] ?? $source['amount'] ?? $source['final_total'] ?? -1
        );
    }

    private function externalMoneyAmount(mixed $value): float
    {
        if (is_array($value)) {
            $value = $value['amount'] ?? $value['value'] ?? 0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * @return array{subtotal: float, delivery: float, discount: float, vat: float, total: float, subscription_api_payload: array<string, string>}
     */
    private function resolvePaymentAmounts(
        bool $isSubscriptionCheckout,
        array $cart,
        array $validated,
        int $planDurationId,
        float $subtotal,
        float $deliveryFee,
        float $discountAmount,
        float $vatRate,
    ): array {
        $subscriptionApiPayload = [];
        $total = $subtotal + $deliveryFee - $discountAmount;
        $vatAmount = round($total - ($total / (1 + $vatRate)), 2);

        if (! $isSubscriptionCheckout) {
            return [
                'subtotal' => $subtotal,
                'delivery' => $deliveryFee,
                'discount' => $discountAmount,
                'vat' => $vatAmount,
                'total' => $total,
                'subscription_api_payload' => [],
            ];
        }

        $subscriptionApiPayload = SubscriptionCheckoutPayload::buildFormPayload(
            $validated,
            $cart,
            $this->externalDataService,
            $planDurationId > 0 ? $planDurationId : null,
        );

        $token = (string) session('external_api_token', '');
        if ($token !== '' && $subscriptionApiPayload !== []) {
            $calc = $this->accountApiService->calculateSubscription($subscriptionApiPayload, $token);
            $parsed = $this->accountApiService->parseSubscriptionCalculateTotals($calc['data'] ?? null);
            if ($parsed !== null) {
                return [
                    'subtotal' => $parsed['subtotal'],
                    'delivery' => $parsed['delivery'],
                    'discount' => $parsed['discount'] > 0 ? $parsed['discount'] : $discountAmount,
                    'vat' => $parsed['vat'],
                    'total' => $parsed['total'],
                    'subscription_api_payload' => $subscriptionApiPayload,
                ];
            }
        }

        return [
            'subtotal' => $subtotal,
            'delivery' => $deliveryFee,
            'discount' => $discountAmount,
            'vat' => $vatAmount,
            'total' => $total,
            'subscription_api_payload' => $subscriptionApiPayload,
        ];
    }

    /**
     * POST delivery pin + unit details to external API /addresses (EXTERNAL_API_TOKEN).
     * Called from checkout after the user confirms the map address (and optional building row edits).
     */
    public function syncExternalAddress(Request $request): JsonResponse
    {
        try {
            $data = Validator::make($request->all(), [
                'phone' => 'required|string|max:25',
                'delivery_lat' => 'required|numeric',
                'delivery_lng' => 'required|numeric',
                'street' => 'nullable|string|max:1000',
                'delivery_description' => 'nullable|string|max:1000',
                'delivery_district_id' => 'required|integer|min:1',
                'delivery_type' => 'required|in:home,work,other',
                'delivery_pickup_type' => 'nullable|string|max:50',
                'delivery_title' => 'nullable|string|max:120',
                'building' => 'nullable|string|max:500',
                'region_duration_id' => 'nullable|integer|min:1',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('payment.fill_delivery_first'),
                'errors' => $e->errors(),
            ], 422);
        }

        $normalizedSyncPhone = SaudiPhone::to966((string) ($data['phone'] ?? ''));
        if ($normalizedSyncPhone === '') {
            return response()->json([
                'success' => false,
                'message' => __('checkout.phone_saudi_invalid'),
                'errors' => ['phone' => [__('checkout.phone_saudi_invalid')]],
            ], 422);
        }
        $data['phone'] = $normalizedSyncPhone;

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
        if (! is_string($userToken) || $userToken === '') {
            return response()->json([
                'success' => false,
                'message' => __('checkout.verify_phone_to_save_address'),
            ], 401);
        }

        $auth = app(ApiAuthService::class);
        $existing = $auth->getAddresses($userToken, true, true);
        $candidate = array_merge($payload, [
            'district_id' => (int) $data['delivery_district_id'],
            'latitude' => (float) $data['delivery_lat'],
            'longitude' => (float) $data['delivery_lng'],
        ]);
        $duplicate = AddressCheckoutHelper::findDuplicate($existing, $candidate);
        if ($duplicate !== null) {
            $duplicateId = (int) ($duplicate['id'] ?? 0);
            $regionDurationId = (int) ($data['region_duration_id'] ?? 0);
            if ($duplicateId > 0 && $regionDurationId > 0) {
                $this->rememberCheckoutRegionDuration($duplicateId, $regionDurationId);
            }
            if ($duplicateId > 0) {
                $enriched = $auth->loadAddressForSubscription($userToken, $duplicateId);
                if (is_array($enriched)) {
                    $duplicate = $enriched;
                }
            }

            return response()->json([
                'success' => true,
                'already_saved' => true,
                'message' => __('checkout.address_already_saved'),
                'data' => $duplicate,
                'addresses' => $this->checkoutManageableAddresses($userToken),
            ]);
        }

        $result = $auth->storeAddress($userToken, $payload);

        if (($result['skipped'] ?? false) === true) {
            return response()->json(['success' => true, 'skipped' => true]);
        }

        $httpOk = (bool) ($result['_http_ok'] ?? false);
        $apiStatus = (int) ($result['status'] ?? 0);
        $hasData = array_key_exists('data', $result);

        if ($httpOk && ($apiStatus === 200 || $hasData)) {
            $stored = AddressCheckoutHelper::unwrapStoredAddress($result['data'] ?? null);
            $regionDurationId = (int) ($data['region_duration_id'] ?? 0);
            if (is_array($stored) && (int) ($stored['id'] ?? 0) > 0) {
                $storedId = (int) $stored['id'];
                $enriched = $auth->loadAddressForSubscription($userToken, $storedId);
                if (is_array($enriched)) {
                    $stored = $enriched;
                }
                if ($regionDurationId > 0) {
                    $this->rememberCheckoutRegionDuration($storedId, $regionDurationId);
                }

                return response()->json([
                    'success' => true,
                    'data' => $stored,
                    'addresses' => $this->checkoutManageableAddresses($userToken),
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'] ?? null,
                'addresses' => $this->checkoutManageableAddresses($userToken),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => (string) ($result['message'] ?? __('address.save_failed')),
        ], 422);
    }

    /**
     * POST /checkout/select-address — POST /addresses/{id}/days (same as mobile app).
     */
    public function selectCheckoutAddress(Request $request): JsonResponse
    {
        $token = session('external_api_token');
        if (! is_string($token) || $token === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.verify_phone_to_pay'),
            ], 403);
        }

        try {
            $data = Validator::make($request->all(), [
                'address_id' => 'required|integer|min:1',
                'region_duration_id' => 'nullable|integer|min:1',
            ])->validate();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.confirm_saved_address_before_payment'),
                'errors' => $e->errors(),
            ], 422);
        }

        $addressId = (int) $data['address_id'];
        $regionDurationId = (int) ($data['region_duration_id'] ?? 0);
        $auth = app(ApiAuthService::class);
        $address = $auth->loadAddressForSubscription($token, $addressId);
        if (! is_array($address)) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.confirm_saved_address_before_payment'),
            ], 422);
        }

        if ($regionDurationId > 0) {
            $slots = AddressCheckoutHelper::normalizeRegionDurations(AddressCheckoutHelper::districtDurations($address));
            if ($slots === []) {
                $slots = $auth->findDistrictRegionDurations(
                    $token,
                    AddressCheckoutHelper::districtId($address),
                    $addressId,
                );
            }
            if ($slots !== [] && ! AddressCheckoutHelper::isValidRegionDurationId($regionDurationId, $slots)) {
                return response()->json([
                    'success' => false,
                    'message' => __('checkout.select_delivery_time'),
                ], 422);
            }
            $this->rememberCheckoutRegionDuration($addressId, $regionDurationId);
        } elseif ($this->checkoutRegionDurationId($addressId) <= 0) {
            $autoId = AddressCheckoutHelper::firstRegionDurationId($address);
            if ($autoId > 0) {
                $this->rememberCheckoutRegionDuration($addressId, $autoId);
            }
        }

        $cart = session()->get(CartManager::SESSION_SUBSCRIPTION)
            ?? session()->get(CartManager::SESSION_MARKET, []);
        $withWeekend = SubscriptionCheckoutPayload::resolveWithWeekend($cart, $this->externalDataService);
        $defaultDays = SubscriptionCheckoutPayload::defaultDeliveryWeekdays($withWeekend === '1');

        $sync = $auth->syncAddressDeliveryDaysForCheckout(
            $token,
            $addressId,
            $defaultDays,
            $withWeekend === '1',
        );

        if (! ($sync['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => (string) ($sync['message'] ?? __('address.save_failed')),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $sync['address'],
            'already_active' => (bool) ($sync['already_active'] ?? false),
            'addresses' => $this->checkoutManageableAddresses($token),
        ]);
    }

    /**
     * DELETE /checkout/addresses/{addressId} — remove a saved address during checkout.
     */
    public function deleteCheckoutAddress(int $addressId): JsonResponse
    {
        $token = session('external_api_token');
        if (! is_string($token) || $token === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.verify_phone_to_pay'),
            ], 403);
        }

        if ($addressId <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.confirm_saved_address_before_payment'),
            ], 422);
        }

        $auth = app(ApiAuthService::class);
        $address = $auth->findAddressById($token, $addressId, false);
        if (! is_array($address)) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.confirm_saved_address_before_payment'),
            ], 422);
        }

        $result = $auth->deleteAddress($token, $addressId);
        if (! filter_var($result['success'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return response()->json([
                'success' => false,
                'message' => (string) ($result['message'] ?? __('address.delete_failed')),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => __('address.deleted'),
            'addresses' => $this->checkoutManageableAddresses($token),
        ]);
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

        $addresses = $this->checkoutManageableAddresses($token);

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
     * GET /checkout/subscription-schedule — authoritative min start date from API calculate.
     */
    public function subscriptionSchedule(Request $request): JsonResponse
    {
        $token = session('external_api_token');
        if (! is_string($token) || $token === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.verify_phone_to_pay'),
                'needs_auth' => true,
            ], 403);
        }

        $cart = session()->get(CartManager::SESSION_SUBSCRIPTION)
            ?? session()->get(CartManager::SESSION_MARKET, []);

        if ($cart === []) {
            return response()->json([
                'success' => false,
                'message' => __('Your cart is empty'),
            ], 422);
        }

        $hasPlanItems = collect($cart)->contains(fn ($item) => ! empty($item['options']['duration_days']));
        if (! $hasPlanItems) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.invalid_plan_duration'),
            ], 422);
        }

        $withWeekend = SubscriptionCheckoutPayload::resolveWithWeekend($cart, $this->externalDataService);
        $deliveryType = (string) $request->query('delivery_type', 'home');
        $selectedAddressId = (int) $request->query('selected_address_id', 0);
        $branchId = (int) $request->query('branch_id', 0);

        if ($deliveryType === 'pickup' && $branchId <= 0) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.payment_blocker_pickup'),
                'needs_branch' => true,
            ], 422);
        }

        $validated = [
            'plan_duration_id' => (int) $request->query('plan_duration_id', 0),
            'delivery_type' => $deliveryType,
            'with_weekend' => $request->query('with_weekend', $withWeekend),
            'selected_address_id' => $selectedAddressId > 0 ? $selectedAddressId : null,
            'branch_id' => $branchId > 0 ? $branchId : null,
            'zone_id' => (int) $request->query('zone_id', 0),
        ];

        $payload = SubscriptionCheckoutPayload::buildMinimalSchedulePayload(
            $cart,
            $this->externalDataService,
            $validated,
        );

        if ($payload === []) {
            return response()->json([
                'success' => false,
                'message' => __('checkout.invalid_plan_duration'),
            ], 422);
        }

        $calc = $this->accountApiService->calculateSubscription($payload, $token);
        if (! ($calc['ok'] ?? false)) {
            $message = (string) ($calc['message'] ?? __('account.request_failed'));
            $rawMin = SubscriptionCheckoutPayload::extractRawMinStartDateFromApiResult($calc);
            if ($rawMin === '') {
                $rawMin = SubscriptionCheckoutPayload::parseRawMinimumDateFromValidationMessage($message);
            }
            if ($rawMin !== '') {
                $displayMin = SubscriptionCheckoutPayload::extractApiMinStartDate(['first_available_date_for_subscription' => $rawMin]);
                if ($displayMin === '') {
                    $displayMin = SubscriptionCheckoutPayload::computeFallbackMinStartDate($withWeekend === '1');
                }

                return response()->json([
                    'success' => true,
                    'first_available_date_for_subscription' => $displayMin,
                    'min_start_date' => $displayMin,
                    'api_min_start_date' => $rawMin,
                    'with_weekend' => $withWeekend,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => $message !== '' ? $message : __('account.request_failed'),
                'min_start_date' => null,
            ], 422);
        }

        $data = is_array($calc['data'] ?? null) ? $calc['data'] : [];
        $rawMin = SubscriptionCheckoutPayload::extractRawApiMinStartDate($data);
        $displayMin = SubscriptionCheckoutPayload::extractApiMinStartDate($data);

        if ($displayMin === '' && $rawMin !== '') {
            $displayMin = $rawMin;
        }

        if ($displayMin === '') {
            $displayMin = SubscriptionCheckoutPayload::computeFallbackMinStartDate($withWeekend === '1');
        }

        return response()->json([
            'success' => true,
            'first_available_date_for_subscription' => $displayMin,
            'min_start_date' => $displayMin,
            'api_min_start_date' => $rawMin !== '' ? $rawMin : null,
            'with_weekend' => $withWeekend,
        ]);
    }

    /**
     * GET /checkout/subscription-status — poll external subscription until paid/active.
     */
    public function subscriptionStatus(Request $request): JsonResponse
    {
        $token = session('external_api_token');
        if (! is_string($token) || $token === '') {
            return response()->json(['success' => false, 'message' => __('account.login_required')], 403);
        }

        $subscriptionId = (int) $request->query('id', 0);
        if ($subscriptionId <= 0) {
            return response()->json(['success' => false, 'message' => __('account.request_failed')], 422);
        }

        $result = $this->accountApiService->showSubscription($subscriptionId);
        if (! ($result['ok'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => (string) ($result['message'] ?? __('account.request_failed')),
            ], 502);
        }

        $sub = $this->extractSubscriptionRow($result['data'] ?? null, $subscriptionId);

        if (! $this->accountApiService->isSubscriptionPaymentConfirmed($sub)) {
            $pending = is_array(session('pending_subscription_confirm'))
                ? session('pending_subscription_confirm')
                : ['subscription_id' => $subscriptionId];

            if ((int) ($pending['subscription_id'] ?? 0) !== $subscriptionId) {
                $pending['subscription_id'] = $subscriptionId;
            }

            $moyasarId = trim((string) $request->query('moyasar_id', ''));
            if ($moyasarId !== '') {
                $pending['moyasar_id'] = $moyasarId;
            }
            $paymentId = (int) $request->query('payment_id', 0);
            if ($paymentId > 0) {
                $pending['external_payment_id'] = $paymentId;
            }
            if ($request->query('status')) {
                $pending['status'] = (string) $request->query('status');
            }
            if (empty($pending['external_payment_id'])) {
                $pending['external_payment_id'] = (int) data_get($sub, 'payment.id', 0);
            }

            $this->accountApiService->maybeForwardPendingMoyasarCallback($pending, $token, $sub);
            $retry = $this->accountApiService->showSubscription($subscriptionId);
            if ($retry['ok'] ?? false) {
                $sub = $this->extractSubscriptionRow($retry['data'] ?? null, $subscriptionId);
            }
        }

        $status = strtolower((string) ($sub['status'] ?? ''));
        $paymentStatus = strtolower((string) ($sub['payment_status'] ?? data_get($sub, 'payment.status', '')));
        $confirmed = $this->accountApiService->isSubscriptionPaymentConfirmed($sub);

        if ($confirmed) {
            session()->forget('pending_subscription_confirm');
            session()->forget('checkout_api_subscription_checkout');
        }

        return response()->json([
            'success' => true,
            'subscription_id' => $subscriptionId,
            'status' => $status,
            'payment_status' => $paymentStatus,
            'confirmed' => $confirmed,
            'subscription' => $sub,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function extractSubscriptionRow(mixed $data, int $subscriptionId): array
    {
        if (! is_array($data)) {
            return [];
        }

        if (isset($data['id']) || isset($data['status'])) {
            return $data;
        }

        foreach (['subscriptions', 'data', 'items'] as $key) {
            $list = $data[$key] ?? null;
            if (! is_array($list)) {
                continue;
            }
            foreach ($list as $row) {
                if (! is_array($row)) {
                    continue;
                }
                if ((int) ($row['id'] ?? 0) === $subscriptionId) {
                    return $row;
                }
            }
        }

        return [];
    }

    /**
     * Create or update a pending payment and return Moyasar bootstrap data (after phone OTP verification).
     */
    public function moyasarSession(Request $request, MoyasarPaymentService $moyasarService): JsonResponse
    {
        $verified = session('phone_verified');
        $phone = (string) $request->input('phone', '');
        $previewOnly = filter_var($request->input('preview_only', false), FILTER_VALIDATE_BOOLEAN);
        $phoneNorm = self::normalizePhoneForMatch($phone);
        $verifiedNorm = self::normalizePhoneForMatch((string) $verified);
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
            'phone' => 'required|string|max:25',
            'start_date' => $hasPlanItems ? 'required|string|max:50' : 'nullable|string|max:50',
            'duration' => $hasPlanItems ? 'required|in:once,weekly,monthly,3months' : 'nullable|in:once,weekly,monthly,3months',
            'delivery_type' => 'required|in:home,pickup',
            'coupon' => 'nullable|string|max:50',
            'promocode_name' => 'nullable|string|max:50',
            'branch_id' => 'required_if:delivery_type,pickup|nullable|integer',
            // Do not require zone_id at validator level for home delivery:
            // saved-address flow may only send selected_address_id and we
            // resolve zone_id server-side below.
            'zone_id' => 'nullable|integer',
            'selected_address_id' => 'nullable|integer',
            'region_duration_id' => 'nullable|integer|min:1',
        ];
        if ($hasPlanItems) {
            $rules['plan_duration_id'] = 'required|integer';
        } else {
            $rules['plan_duration_id'] = 'nullable|integer';
        }

        try {
            $request->merge([
                'selected_address_id' => $request->input('selected_address_id') ?: null,
                'zone_id' => $request->input('zone_id') ?: null,
            ]);
            $validated = Validator::make($request->all(), $rules)->validate();
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('payment.fill_delivery_first'),
                'errors' => $e->errors(),
            ], 422);
        }

        $normalizedMoyasarPhone = SaudiPhone::to966((string) ($validated['phone'] ?? ''));
        if ($normalizedMoyasarPhone === '') {
            return response()->json([
                'success' => false,
                'message' => __('checkout.phone_saudi_invalid'),
                'errors' => ['phone' => [__('checkout.phone_saudi_invalid')]],
            ], 422);
        }
        $validated['phone'] = $normalizedMoyasarPhone;

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

        // Safety net: when the client only sends `selected_address_id` (saved
        // address flow) and the frontend couldn't resolve the zone locally, we
        // must resolve it server-side. Without this, delivery fee falls back
        // to the flat settings value and the payment session gets a wrong
        // total for zone-priced deliveries.
        if (
            ($validated['delivery_type'] ?? '') === 'home'
            && empty($validated['zone_id'])
            && ! empty($validated['selected_address_id'])
        ) {
            $token = session('external_api_token');
            if (is_string($token) && $token !== '') {
                $savedAddresses = $this->checkoutActiveAddresses($token);
                $picked = collect($savedAddresses)->first(
                    fn ($a) => (int) ($a['id'] ?? 0) === (int) $validated['selected_address_id']
                );
                if (is_array($picked)) {
                    $resolvedZoneId = $picked['city']['id']
                        ?? $picked['city_id']
                        ?? $picked['zone_id']
                        ?? $picked['zone']['id']
                        ?? $picked['district']['city_id']
                        ?? $picked['district']['zone_id']
                        ?? $picked['district']['city']['id']
                        ?? $picked['district']['zone']['id']
                        ?? null;
                    if (! $resolvedZoneId) {
                        $districtId = $picked['district']['id'] ?? $picked['district_id'] ?? null;
                        if ($districtId) {
                            $zonesList = $this->externalDataService->getZones();
                            $match = collect($zonesList)->first(function ($z) use ($districtId) {
                                $districtList = $z['districts'] ?? $z['district_ids'] ?? [];
                                foreach ((array) $districtList as $d) {
                                    $id = is_array($d) ? ($d['id'] ?? $d['district_id'] ?? null) : $d;
                                    if ((int) $id === (int) $districtId) {
                                        return true;
                                    }
                                }

                                return false;
                            });
                            if ($match) {
                                $resolvedZoneId = $match['id'] ?? null;
                            }
                        }
                    }
                    if ($resolvedZoneId) {
                        $validated['zone_id'] = (int) $resolvedZoneId;
                    }
                }
            }
        }

        $validated['duration'] = 'once';

        $isSubscriptionCheckout = $hasPlanItems && session()->has(CartManager::SESSION_SUBSCRIPTION);

        if (
            $isSubscriptionCheckout
            && ($validated['delivery_type'] ?? '') === 'home'
        ) {
            $addressId = (int) ($validated['selected_address_id'] ?? 0);
            if ($addressId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('checkout.confirm_saved_address_before_payment'),
                    'errors' => ['selected_address_id' => [__('checkout.confirm_saved_address_before_payment')]],
                ], 422);
            }

            $token = (string) session('external_api_token', '');
            if ($token === '') {
                return response()->json([
                    'success' => false,
                    'message' => __('payment.verify_phone_to_pay'),
                ], 403);
            }

            $auth = app(ApiAuthService::class);
            $withWeekend = SubscriptionCheckoutPayload::resolveWithWeekend($cart, $this->externalDataService);
            $defaultDays = SubscriptionCheckoutPayload::defaultDeliveryWeekdays($withWeekend === '1');
            $sync = $auth->syncAddressDeliveryDaysForCheckout(
                $token,
                $addressId,
                $defaultDays,
                $withWeekend === '1',
            );
            if (! ($sync['ok'] ?? false)) {
                $message = (string) ($sync['message'] ?? __('address.save_failed'));

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => ['selected_address_id' => [$message]],
                ], 422);
            }

            $regionDurationId = (int) ($validated['region_duration_id'] ?? 0);
            if ($regionDurationId > 0) {
                $this->rememberCheckoutRegionDuration($addressId, $regionDurationId);
            } elseif ($this->checkoutRegionDurationId($addressId) <= 0) {
                $address = $auth->loadAddressForSubscription($token, $addressId);
                if (is_array($address)) {
                    $autoId = AddressCheckoutHelper::firstRegionDurationId($address);
                    if ($autoId > 0) {
                        $this->rememberCheckoutRegionDuration($addressId, $autoId);
                        $validated['region_duration_id'] = $autoId;
                    }
                }
            } else {
                $validated['region_duration_id'] = $this->checkoutRegionDurationId($addressId);
            }
        }

        if (
            $isSubscriptionCheckout
            && ($validated['delivery_type'] ?? '') === 'pickup'
        ) {
            $branchId = (int) ($validated['branch_id'] ?? 0);
            if ($branchId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('checkout.payment_blocker_pickup'),
                    'errors' => ['branch_id' => [__('checkout.payment_blocker_pickup')]],
                ], 422);
            }
        }

        $planDurationsFromApi = [];
        if ($hasPlanItems) {
            $firstKey = null;
            foreach ($cart as $key => $item) {
                if (! empty($item['options']['duration_days'])) {
                    $firstKey = $key;
                    break;
                }
            }
            $programId = $firstKey !== null ? (int) ($cart[$firstKey]['id'] ?? 0) : 0;
            $planDurationsFromApi = $programId > 0 ? $this->externalDataService->getCheckoutPlanDurations($programId) : [];
            $cartDurationDays = $firstKey !== null ? (int) ($cart[$firstKey]['options']['duration_days'] ?? 0) : 0;
            $requestedId = (int) $request->input('plan_duration_id', 0);
            if ($requestedId > 0) {
                $selectedRow = collect($planDurationsFromApi)->first(
                    fn ($d) => (int) ($d['id'] ?? 0) === $requestedId
                );
                if ($selectedRow) {
                    $cartDurationDays = (int) ($selectedRow['days'] ?? $cartDurationDays);
                }
            }
            $resolvedDurationId = SubscriptionCheckoutPayload::resolvePlanDurationId(
                $programId,
                $requestedId,
                $cartDurationDays,
                $this->externalDataService,
            );
            if ($planDurationsFromApi !== [] && $resolvedDurationId <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('checkout.invalid_plan_duration'),
                    'errors' => ['plan_duration_id' => [__('checkout.invalid_plan_duration')]],
                ], 422);
            }
            if ($resolvedDurationId > 0) {
                $match = collect($planDurationsFromApi)->first(fn ($d) => (int) ($d['id'] ?? 0) === $resolvedDurationId);
                if ($match && $firstKey !== null) {
                    $linePrice = self::planDurationEffectivePrice($match);
                    $cart[$firstKey]['price'] = $linePrice;
                    $cart[$firstKey]['options']['duration_id'] = (string) ($match['id'] ?? $resolvedDurationId);
                    $cart[$firstKey]['options']['duration_days'] = (int) ($match['days'] ?? 0);
                    if (session()->has(CartManager::SESSION_SUBSCRIPTION)) {
                        session()->put(CartManager::SESSION_SUBSCRIPTION, $cart);
                    } else {
                        session()->put(CartManager::SESSION_MARKET, $cart);
                    }
                }
                $validated['plan_duration_id'] = $resolvedDurationId;
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
        $couponCode = $validated['promocode_name'] ?? ($validated['coupon'] ?? null);

        $vatRate = (float) Setting::getValue('vat_rate', 15) / 100;
        $isSubscriptionCheckout = $hasPlanItems && session()->has(CartManager::SESSION_SUBSCRIPTION);
        $planDurationId = (int) ($validated['plan_duration_id'] ?? $request->input('plan_duration_id', 0));
        $amounts = $this->resolvePaymentAmounts(
            $isSubscriptionCheckout,
            $cart,
            $validated,
            $planDurationId,
            $subtotal,
            $deliveryFee,
            $discountAmount,
            $vatRate,
        );
        $subtotal = $amounts['subtotal'];
        $deliveryFee = $amounts['delivery'];
        $discountAmount = $amounts['discount'];
        $vatAmount = $amounts['vat'];
        $total = $amounts['total'];
        $amountInHalalas = (int) round($total * 100);
        $subscriptionApiPayload = $amounts['subscription_api_payload'];

        if ($isSubscriptionCheckout && ! $previewOnly) {
            if ($subscriptionApiPayload === []) {
                return response()->json([
                    'success' => false,
                    'message' => __('checkout.invalid_plan_duration'),
                    'errors' => ['plan_duration_id' => [__('checkout.invalid_plan_duration')]],
                ], 422);
            }
            $normalizedStart = SubscriptionCheckoutPayload::normalizeStartDate($validated['start_date'] ?? '');
            if ($normalizedStart === '') {
                return response()->json([
                    'success' => false,
                    'message' => __('checkout.start_date_required'),
                    'errors' => ['start_date' => [__('checkout.start_date_required')]],
                ], 422);
            }
            $subscriptionApiPayload['date'] = $normalizedStart;
            $subscriptionApiPayload['start_date'] = $normalizedStart;
        }

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

        if ($isSubscriptionCheckout) {
            return $this->subscriptionMoyasarSessionResponse($subscriptionApiPayload, $moyasarService);
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

        $checkoutPayload = [
            'delivery_type' => $validated['delivery_type'] ?? null,
            'branch_id' => $validated['branch_id'] ?? null,
            'selected_address_id' => $validated['selected_address_id'] ?? null,
            'zone_id' => $validated['zone_id'] ?? null,
            'note' => (string) ($request->input('note', '') ?? ''),
            'payment_option' => 'credit_card',
            'use_wallet' => (string) ($request->input('useWallet', '0') ?? '0'),
            'plan_duration_id' => $planDurationId > 0 ? $planDurationId : null,
        ];
        if ($subscriptionApiPayload !== []) {
            $checkoutPayload['subscription_api'] = $subscriptionApiPayload;
        }

        $paymentData = [
            'kind' => $isSubscriptionCheckout ? PaymentKind::Subscription : PaymentKind::Order,
            'amount' => $amountInHalalas,
            'currency' => 'SAR',
            'subtotal' => (int) round($subtotal * 100),
            'delivery_fee' => (int) round($deliveryFee * 100),
            'vat_amount' => (int) round($vatAmount * 100),
            'discount_amount' => (int) round($discountAmount * 100),
            'customer_name' => '—',
            'customer_email' => null,
            'customer_phone' => $phone,
            'customer_phone_normalized' => self::normalizePhoneForMatch($phone),
            'cart_items' => $cart,
            'checkout_payload' => $checkoutPayload,
            'start_date' => $validated['start_date'] ?? now()->format('Y-m-d'),
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

    /**
     * @param  array<string, string>  $subscriptionApiPayload
     */
    private function subscriptionMoyasarSessionResponse(
        array $subscriptionApiPayload,
        MoyasarPaymentService $moyasarService,
    ): JsonResponse {
        if ($subscriptionApiPayload === []) {
            return response()->json([
                'success' => false,
                'message' => __('payment.fill_delivery_first'),
            ], 422);
        }

        $token = (string) session('external_api_token', '');
        if ($token === '') {
            return response()->json([
                'success' => false,
                'message' => __('payment.verify_phone_to_pay'),
            ], 403);
        }

        $boot = $this->accountApiService->bootstrapSubscriptionMoyasar($subscriptionApiPayload, $token);
        if (! ($boot['ok'] ?? false) || ! is_array($boot['bootstrap'] ?? null)) {
            $message = (string) ($boot['message'] ?? __('account.request_failed'));
            $minStart = (string) ($boot['min_start_date'] ?? '');
            if ($minStart === '') {
                $minStart = SubscriptionCheckoutPayload::parseRawMinimumDateFromValidationMessage($message);
            }
            if (str_contains($message, 'PlanDuration')) {
                $message = __('checkout.invalid_plan_duration');
            }
            if ($minStart !== '') {
                $message = SubscriptionCheckoutPayload::resolveStartDateErrorMessage($message, $minStart);
            }
            $response = [
                'success' => false,
                'message' => $message,
                'errors' => [],
            ];
            if ($minStart !== '') {
                $response['min_start_date'] = $minStart;
                $response['errors'] = ['start_date' => [$message]];
            }

            return response()->json($response, 422);
        }

        $bootstrap = $boot['bootstrap'];
        $subscriptionId = (int) ($boot['subscription_id'] ?? $bootstrap['subscription_id'] ?? 0);
        $publishableKey = trim((string) ($bootstrap['publishable_key'] ?? ''));

        if (! $moyasarService->isValidPublishableKey($publishableKey)) {
            Log::warning('Subscription Moyasar bootstrap missing valid publishable key from API', [
                'api_key' => $bootstrap['publishable_key'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => __('payment.moyasar_key_missing'),
                'errors' => [],
            ], 422);
        }

        return response()->json(array_filter([
            'success' => true,
            'preview' => false,
            'api_checkout' => true,
            'subscription_id' => $subscriptionId,
            'amount_halalas' => (int) $bootstrap['amount_halalas'],
            'publishable_key' => $publishableKey,
            'callback_url' => route('payment.callback', ['subscription' => $subscriptionId > 0 ? $subscriptionId : null]),
            'currency' => (string) ($bootstrap['currency'] ?? 'SAR'),
            'description' => (string) ($bootstrap['description'] ?? __('payment.subscription_checkout_description')),
            'metadata' => is_array($bootstrap['metadata'] ?? null) ? $bootstrap['metadata'] : [],
            'adjusted_start_date' => (string) ($boot['adjusted_start_date'] ?? ''),
        ], static fn ($v) => $v !== null && $v !== ''));
    }
}
