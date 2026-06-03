<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ExternalDataService
{
    protected string $baseUrl;

    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.external_api.url', 'https://diet-watchers-stage-fbofszkn.on-forge.com/api'), '/');
        $this->token = config('services.external_api.token');
    }

    /**
     * Resolve program/meal image paths from the external API to absolute URLs.
     * Root-relative paths (e.g. /storage/...) must load from the API host, not the Laravel site.
     */
    protected function absoluteMediaUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }
        $origin = preg_replace('#/api/?$#i', '', $this->baseUrl);
        $origin = rtrim((string) $origin, '/');
        if ($origin === '') {
            return $url;
        }
        if (str_starts_with($url, '/')) {
            return $origin.$url;
        }
        if (preg_match('#^(storage|uploads?)/#i', $url)) {
            return $origin.'/'.$url;
        }

        return $url;
    }

    protected function http(): PendingRequest
    {
        $locale = app()->getLocale();

        $request = Http::withOptions([
            'timeout' => 10,
            'connect_timeout' => 5,
        ])->acceptJson()
            ->withHeaders([
                'Accept-Language' => $locale,
            ]);

        if ($this->token) {
            $request = $request->withToken($this->token);
        }

        return $request;
    }

    /**
     * Expose the configured HTTP client (e.g. for one-off form posts outside cached helpers).
     */
    public function httpClient(): PendingRequest
    {
        return $this->http();
    }

    /**
     * Generate a locale-aware cache key.
     */
    protected function cacheKey(string $key): string
    {
        return app()->getLocale().'_'.$key;
    }

    // ─── Programs / Plans ────────────────────────────────────────

    /**
     * Get all programs from API, transformed to frontend format.
     *
     * Frontend expects: id, name, image_url, price (number), duration_days,
     * calories_per_day, category_id, category.name, description
     */
    public function getPrograms(?int $categoryId = null): array
    {
        $cacheKey = $this->cacheKey($categoryId ? "programs_cat_{$categoryId}" : 'programs');

        return Cache::remember($cacheKey, 3600, function () use ($categoryId) {
            try {
                $params = [];
                if ($categoryId) {
                    $params['category_id'] = $categoryId;
                }
                $response = $this->http()->get("{$this->baseUrl}/programs", $params);
                if ($response->successful()) {
                    $raw = $response->json('data', []);

                    return array_map([$this, 'transformProgram'], $raw);
                }
            } catch (\Exception $e) {
                Log::warning('External API /programs failed: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Get a single program detail as an object (for detail page).
     * Falls back to finding from the programs list if the detail endpoint fails.
     */
    public function getProgram(int $id): ?object
    {
        // Try the detail endpoint first
        try {
            $response = $this->http()->get("{$this->baseUrl}/programs/{$id}");
            if ($response->successful() && ! isset($response->json()['exception'])) {
                $raw = $response->json('data');
                if ($raw) {
                    return (object) $this->transformProgram($raw);
                }
            }
        } catch (\Exception $e) {
            Log::warning("External API /programs/{$id} failed: ".$e->getMessage());
        }

        // Fallback: find from the programs list
        $programs = $this->getPrograms();
        foreach ($programs as $program) {
            if ((int) $program['id'] === $id) {
                return (object) $program;
            }
        }

        return null;
    }

    /**
     * Transform raw API program into the format the frontend expects.
     */
    protected function moneyAmount(mixed $value): float
    {
        if (is_array($value)) {
            $value = $value['amount'] ?? $value['value'] ?? 0;
        }

        return is_numeric($value) ? (float) $value : 0.0;
    }

    /**
     * Extract lowest valid PROGRAM PACKAGE total from durations only.
     *
     * Important: ignore plan/day pricing fields (can be per-day and too low),
     * and only consider total duration package prices.
     */
    protected function extractProgramMinPrice(array $program): float
    {
        $prices = [];

        $push = static function (array &$bucket, float $v): void {
            if ($v > 0) {
                $bucket[] = $v;
            }
        };

        foreach (($program['plans'] ?? []) as $plan) {
            if (! is_array($plan)) {
                continue;
            }

            foreach (($plan['durations'] ?? []) as $d) {
                if (! is_array($d)) {
                    continue;
                }
                $effective = $this->moneyAmount($d['effective_price'] ?? 0);
                $offer = $this->moneyAmount($d['offer_price'] ?? 0);
                $price = $this->moneyAmount($d['price'] ?? 0);

                $push($prices, $effective);
                $push($prices, $offer);
                $push($prices, $price);
            }
        }

        return ! empty($prices) ? (float) min($prices) : 0.0;
    }

    protected function transformProgram(array $program): array
    {
        if (! empty($program['profile']) && is_array($program['profile'])) {
            return $this->transformProgramFromProfilePayload($program);
        }

        $price = $program['price'] ?? [];
        $offerPrice = $program['offer_price'] ?? [];
        $calories = $program['calories'] ?? [];

        $priceAmount = is_array($price) ? ($price['amount'] ?? 0) : $price;
        $offerPriceAmount = is_array($offerPrice) ? ($offerPrice['amount'] ?? 0) : $offerPrice;

        $calMin = (int) ($calories['min'] ?? 0);
        $calMax = (int) ($calories['max'] ?? 0);
        $caloriesPerDay = $calMax ?: $calMin;

        // Build calorie options for detail page
        $calorieOptions = [];
        if ($calMin && $calMax && $calMin !== $calMax) {
            $step = (int) (($calMax - $calMin) / 3);
            if ($step > 0) {
                $calorieOptions[] = ['range' => "{$calMin}-".($calMin + $step), 'label' => "{$calMin}-".($calMin + $step)];
                $mid = $calMin + $step + 1;
                $calorieOptions[] = ['range' => "{$mid}-".($mid + $step), 'label' => "{$mid}-".($mid + $step)];
                $calorieOptions[] = ['range' => ($mid + $step + 1)."-{$calMax}", 'label' => ($mid + $step + 1)."-{$calMax}"];
            } else {
                $calorieOptions[] = ['range' => "{$calMin}-{$calMax}", 'label' => "{$calMin}-{$calMax}"];
            }
        } elseif ($calMax) {
            $calorieOptions[] = ['range' => "{$calMax}", 'label' => "{$calMax} kcal"];
        }

        $minPrice = $this->extractProgramMinPrice($program);

        return [
            'id' => $program['id'],
            'name' => $program['name'] ?? '',
            'description' => $program['description'] ?? '',
            'image_url' => $this->absoluteMediaUrl((string) ($program['image'] ?? '')),
            'price' => (int) $priceAmount,
            'offer_price' => (int) $offerPriceAmount,
            'min_price' => (int) round($minPrice > 0 ? $minPrice : ((float) $offerPriceAmount > 0 ? (float) $offerPriceAmount : (float) $priceAmount)),
            'duration_days' => $program['duration_days'] ?? 28,
            'calories_per_day' => $caloriesPerDay,
            'calories_min' => $calMin,
            'calories_max' => $calMax,
            'calorie_options' => $calorieOptions,
            'category_id' => $program['category_id'] ?? null,
            'category' => $program['category'] ?? ['id' => null, 'name' => ''],
            'badges' => $program['badges'] ?? [],
            'weekly_price' => $program['weeklyPrice']['withoutDiscount']['amount'] ?? null,
        ];
    }

    /**
     * /programs/{id} payload with profile + subscription plans (variant menus, calories, durations).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function transformProgramFromProfilePayload(array $data): array
    {
        $profile = $data['profile'] ?? [];
        $programId = (int) ($profile['id'] ?? 0);
        $price = $profile['price'] ?? [];
        $offerPrice = $profile['offer_price'] ?? [];
        $priceAmount = is_array($price) ? (float) ($price['amount'] ?? 0) : (float) $price;
        $offerPriceAmount = is_array($offerPrice) ? (float) ($offerPrice['amount'] ?? 0) : (float) $offerPrice;

        $subscriptionPlans = [];
        foreach ($data['plans'] ?? [] as $plan) {
            if (is_array($plan)) {
                $subscriptionPlans[] = $this->transformSubscriptionPlan($plan);
            }
        }

        $defaultPlanId = (int) data_get($profile, 'defaultPlan.id', 0);
        if ($defaultPlanId > 0 && count($subscriptionPlans) > 1) {
            usort($subscriptionPlans, static function (array $a, array $b) use ($defaultPlanId): int {
                $aIs = (int) ($a['id'] ?? 0) === $defaultPlanId;
                $bIs = (int) ($b['id'] ?? 0) === $defaultPlanId;
                if ($aIs && ! $bIs) {
                    return -1;
                }
                if (! $aIs && $bIs) {
                    return 1;
                }

                return 0;
            });
        }

        $first = $subscriptionPlans[0] ?? null;
        $calorieOptions = $first['calories'] ?? [];
        $defaultCal = collect($calorieOptions)->firstWhere('is_default', true) ?? ($calorieOptions[0] ?? null);
        $caloriesPerDay = 0;
        if ($defaultCal && ! empty($defaultCal['amount'])) {
            if (preg_match('/(\d+)/', (string) $defaultCal['amount'], $m)) {
                $caloriesPerDay = (int) $m[1];
            }
        }

        $profileImage = $this->absoluteMediaUrl((string) ($profile['image'] ?? ''));
        $calMin = (int) data_get($profile, 'calories.min', data_get($profile, 'calories_min', 0));
        $calMax = (int) data_get($profile, 'calories.max', data_get($profile, 'calories_max', 0));

        $defaultPlan = collect($subscriptionPlans)->firstWhere('id', $defaultPlanId);
        $defaultPlanMin = collect($defaultPlan['durations'] ?? [])
            ->map(function (array $d): float {
                $effective = (float) ($d['effective_price'] ?? 0);
                $offer = (float) ($d['offer_price'] ?? 0);
                $price = (float) ($d['price'] ?? 0);
                if ($effective > 0) {
                    return $effective;
                }
                if ($offer > 0 && ($price <= 0 || $offer < $price)) {
                    return $offer;
                }

                return $price;
            })
            ->filter(fn (float $p) => $p > 0)
            ->min();

        $subscriptionMin = collect($subscriptionPlans)
            ->flatMap(fn (array $sp) => $sp['durations'] ?? [])
            ->map(function (array $d): float {
                $effective = (float) ($d['effective_price'] ?? 0);
                $offer = (float) ($d['offer_price'] ?? 0);
                $price = (float) ($d['price'] ?? 0);
                if ($effective > 0) {
                    return $effective;
                }
                if ($offer > 0 && ($price <= 0 || $offer < $price)) {
                    return $offer;
                }

                return $price;
            })
            ->filter(fn (float $p) => $p > 0)
            ->min();

        $fallbackMin = collect([(float) $offerPriceAmount, (float) $priceAmount])->filter(fn (float $p) => $p > 0)->min();
        $resolvedMin = (float) ($defaultPlanMin ?: $subscriptionMin ?: $fallbackMin ?: 0);

        return [
            'id' => $programId,
            'name' => $profile['name'] ?? '',
            'description' => $profile['description'] ?? '',
            'image_url' => $profileImage,
            'profile_image_url' => $profileImage,
            'default_subscription_plan_id' => $defaultPlanId,
            'price' => (int) round($priceAmount),
            'offer_price' => (int) round($offerPriceAmount),
            'min_price' => (int) round($resolvedMin),
            'duration_days' => ($first && ! empty($first['durations'])) ? (int) ($first['durations'][0]['days'] ?? 28) : 28,
            'calories_per_day' => $caloriesPerDay,
            'calories_min' => $calMin,
            'calories_max' => $calMax,
            'calorie_options' => array_map(static function (array $c): array {
                return [
                    'range' => $c['range'],
                    'label' => $c['label'],
                    'id' => $c['id'] ?? 0,
                    'macros' => $c['macros'] ?? null,
                ];
            }, $calorieOptions),
            'category_id' => null,
            'category' => ['id' => null, 'name' => ''],
            'badges' => [],
            'weekly_price' => null,
            'subscription_plans' => $subscriptionPlans,
            'fees' => $data['fees'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $p
     * @return array<string, mixed>
     */
    protected function transformSubscriptionPlan(array $p): array
    {
        $priceDay = $p['price'] ?? [];
        $offerDay = $p['offer_price'] ?? [];

        $durations = [];
        foreach ($p['durations'] ?? [] as $d) {
            if (! is_array($d)) {
                continue;
            }
            $pr = $d['price'] ?? [];
            $ofr = $d['offer_price'] ?? [];
            $del = $d['delivery_price'] ?? [];
            $prAmt = (float) (is_array($pr) ? ($pr['amount'] ?? 0) : $pr);
            $ofrAmt = (float) (is_array($ofr) ? ($ofr['amount'] ?? 0) : $ofr);
            $days = (int) ($d['days'] ?? 0);
            $effective = ($ofrAmt > 0 && $ofrAmt < $prAmt) ? $ofrAmt : $prAmt;
            $durations[] = [
                'id' => (int) ($d['id'] ?? 0),
                'days' => $days,
                'price' => $prAmt,
                'offer_price' => $ofrAmt,
                'list_price' => $prAmt,
                'effective_price' => $effective,
                'has_offer' => $ofrAmt > 0 && $ofrAmt < $prAmt,
                'delivery_price' => (float) (is_array($del) ? ($del['amount'] ?? 0) : $del),
                'is_default' => (bool) ($d['is_default'] ?? false),
                'label' => ($d['days'] ?? 0).' '.__('Days'),
                'start_date' => \App\Support\SubscriptionCheckoutPayload::normalizeStartDate(
                    (string) ($d['start_date'] ?? $d['startDate'] ?? $d['starts_at'] ?? $d['available_from'] ?? '')
                ) ?: null,
            ];
        }

        $calories = [];
        foreach ($p['calories'] ?? [] as $c) {
            if (! is_array($c)) {
                continue;
            }
            $amount = trim((string) ($c['amount'] ?? ''));
            if ($amount === '') {
                continue;
            }
            $normalized = preg_replace('/\s+/', '', str_replace('–', '-', $amount));
            $calories[] = [
                'id' => (int) ($c['id'] ?? 0),
                'amount' => $amount,
                'range' => $normalized,
                'label' => $amount.' '.__('kcal'),
                'is_default' => (bool) ($c['is_default'] ?? false),
                'macros' => $c['macros'] ?? null,
            ];
        }

        $menus = array_values(array_filter(array_map(static fn ($m) => is_string($m) ? trim($m) : '', $p['menus'] ?? [])));

        return [
            'id' => (int) ($p['id'] ?? 0),
            'name' => $p['name'] ?? '',
            'menus' => $menus,
            'menus_display' => $this->formatMenusForDisplay($menus),
            'price_per_day' => (float) (is_array($priceDay) ? ($priceDay['amount'] ?? 0) : $priceDay),
            'offer_price_per_day' => (float) (is_array($offerDay) ? ($offerDay['amount'] ?? 0) : $offerDay),
            'has_offer' => (bool) ($p['has_offer'] ?? false),
            'durations' => $durations,
            'calories' => $calories,
            'image_url' => $this->absoluteMediaUrl((string) ($p['image'] ?? $p['image_url'] ?? '')),
        ];
    }

    /**
     * @param  array<int, string>  $menus
     * @return array<int, string>
     */
    protected function formatMenusForDisplay(array $menus): array
    {
        $out = [];
        foreach ($menus as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            // Keep menu label clean:
            // - hide x1
            // - show count/range only when >1, after text (e.g. "Snack x2", "Snack x2-x3")
            if (preg_match('/^(.+?)\s+[Xx]\s*(\d+)\s*$/u', $line, $m)) {
                $label = trim((string) $m[1]);
                $count = (int) ($m[2] ?? 1);
                $out[] = $count > 1 ? ($label.' x'.$count) : $label;
                continue;
            }
            if (preg_match('/^(.+?)\s+[Xx]\s*(\d+)\s*-\s*(\d+)\s*$/u', $line, $m)) {
                $label = trim((string) $m[1]);
                $min = (int) ($m[2] ?? 1);
                $max = (int) ($m[3] ?? $min);
                if ($min <= 1 && $max <= 1) {
                    $out[] = $label;
                } else {
                    $out[] = $label.' x'.$min.'-x'.$max;
                }
                continue;
            }
            $out[] = $line;
        }

        return $out;
    }

    /**
     * Get program categories from /program-categories API.
     * Falls back to extracting from /programs if the endpoint returns empty.
     */
    public function getCategories(): array
    {
        return Cache::remember($this->cacheKey('program_categories'), 3600, function () {
            // Try /program-categories first
            try {
                $response = $this->http()->get("{$this->baseUrl}/program-categories");
                if ($response->successful()) {
                    $data = $response->json('data', []);
                    if (! empty($data)) {
                        return array_map(function ($cat) {
                            $name = $cat['name'] ?? '';
                            if (is_string($name)) {
                                $decoded = json_decode($name, true);
                                $name = is_array($decoded) ? $decoded : $name;
                            }

                            return [
                                'id' => $cat['id'] ?? 0,
                                'name' => $name,
                                'description' => $cat['description'] ?? '',
                                'image_url' => $this->absoluteMediaUrl((string) ($cat['cover'] ?? $cat['image'] ?? $cat['image_url'] ?? '')),
                                'badge' => $cat['badge'] ?? null,
                                'programs_count' => $cat['programsCount'] ?? 0,
                            ];
                        }, $data);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('External API /program-categories failed: '.$e->getMessage());
            }

            // No categories available from API yet
            return [];
        });
    }

    /**
     * Get categories for homepage display.
     * Falls back to programs if /program-categories is empty.
     */
    public function getCategoriesForDisplay(): array
    {
        $categories = $this->getCategories();

        if (! empty($categories)) {
            return $categories;
        }

        // Fallback: use programs as display categories for homepage
        return array_map(fn (array $p) => [
            'id' => $p['id'],
            'name' => $p['name'],
            'image_url' => $p['image_url'],
        ], $this->getPrograms());
    }

    /**
     * Get plans filtered by category (legacy alias).
     */
    public function getPlans(?int $categoryId = null): array
    {
        $programs = $this->getPrograms();
        if ($categoryId) {
            $programs = array_filter($programs, fn ($p) => ($p['category_id'] ?? null) == $categoryId);
            $programs = array_values($programs);
        }

        return $programs;
    }

    public function getProgramMeals(int $id): array
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/programs/get-meals/{$id}");
            if ($response->successful()) {
                return $response->json('data', []);
            }
        } catch (\Exception $e) {
            Log::warning("External API /programs/get-meals/{$id} failed: ".$e->getMessage());
        }

        return [];
    }

    // ─── Meals ───────────────────────────────────────────────────

    /**
     * Get meals from /meals API with full filter support.
     *
     * @param  array  $filters  Supported: page, group_id, menu_id, tags (array of tag IDs), search (string)
     * @return array{data: array, meta: array}
     */
    public function getMeals(array $filters = []): array
    {
        $page = (int) ($filters['page'] ?? 1);
        $groupId = $filters['group_id'] ?? null;
        $menuId = $filters['menu_id'] ?? null;
        $tags = $filters['tags'] ?? [];
        $search = trim((string) ($filters['search'] ?? ''));

        $cacheKey = $this->cacheKey('meals_'.md5(json_encode($filters)));

        return Cache::remember($cacheKey, 300, function () use ($page, $groupId, $menuId, $tags, $search) {
            try {
                $params = ['page' => $page];
                if ($groupId) {
                    $params['group_id'] = $groupId;
                }
                if ($menuId) {
                    $params['menu_id'] = $menuId;
                }
                if (! empty($tags)) {
                    $params['tags'] = array_values(array_map('intval', $tags));
                }
                if ($search !== '') {
                    $params['search'] = $search;
                }

                $response = $this->http()->get("{$this->baseUrl}/meals", $params);
                if ($response->successful()) {
                    $json = $response->json();
                    $meta = $this->normalizePaginationMeta($json['meta'] ?? [], $page);

                    return [
                        'data' => array_map([$this, 'transformMeal'], $json['data'] ?? []),
                        'meta' => $meta,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('External API /meals failed: '.$e->getMessage());
            }

            return ['data' => [], 'meta' => ['currentPage' => $page, 'lastPage' => 1]];
        });
    }

    /**
     * Get ALL meals across all pages for a given group (used for search).
     * Results are cached to avoid repeated multi-page fetches.
     */
    public function getAllMeals(?int $groupId = null): array
    {
        $cacheKey = $this->cacheKey('all_meals'.($groupId ? "_group_{$groupId}" : ''));

        return Cache::remember($cacheKey, 600, function () use ($groupId) {
            $allMeals = [];
            $page = 1;
            $maxPages = 50;

            do {
                try {
                    $params = ['page' => $page];
                    if ($groupId) {
                        $params['group_id'] = $groupId;
                    }

                    $response = $this->http()->get("{$this->baseUrl}/meals", $params);
                    if ($response->successful()) {
                        $json = $response->json();
                        $data = array_map([$this, 'transformMeal'], $json['data'] ?? []);
                        $allMeals = array_merge($allMeals, $data);
                        $meta = $this->normalizePaginationMeta($json['meta'] ?? [], $page);
                        $lastPage = (int) ($meta['lastPage'] ?? 1);
                    } else {
                        break;
                    }
                } catch (\Exception $e) {
                    Log::warning("External API /meals (page {$page}) failed: ".$e->getMessage());
                    break;
                }

                $page++;
            } while ($page <= $lastPage && $page <= $maxPages);

            return $allMeals;
        });
    }

    /**
     * Get meals by group ID (shortcut for homepage instant orders etc.).
     */
    public function getMealsByGroup(int $groupId, int $page = 1): array
    {
        $result = $this->getMeals(['group_id' => $groupId, 'page' => $page]);

        return $result['data'];
    }

    /**
     * Fetch a single shop meal by ID. Tries GET /meals/{id}, then scans cached /meals list.
     */
    public function getMeal(int $id): ?array
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/meals/{$id}");
            if ($response->successful()) {
                $payload = $response->json();
                $data = $payload['data'] ?? null;
                if (is_array($data) && isset($data['id'])) {
                    return $this->transformMeal($data);
                }
                if (is_array($payload) && isset($payload['id'])) {
                    return $this->transformMeal($payload);
                }
            }
        } catch (\Exception $e) {
            Log::debug("External API /meals/{$id} failed: ".$e->getMessage());
        }

        foreach ($this->getAllMeals(null) as $meal) {
            if ((int) ($meal['id'] ?? 0) === $id) {
                return $meal;
            }
        }

        return null;
    }

    /**
     * Other meals for the detail page (same group when possible).
     *
     * @return array<int, array>
     */
    public function getRelatedMeals(int $excludeId, ?int $groupId, int $limit = 8): array
    {
        $gKey = $groupId !== null && $groupId > 0 ? (string) $groupId : 'none';

        return Cache::remember(
            $this->cacheKey("related_meals_{$excludeId}_{$gKey}_{$limit}"),
            300,
            function () use ($excludeId, $groupId, $limit): array {
                $seen = [$excludeId => true];
                $out = [];

                $push = function (array $rows) use (&$out, &$seen, $limit): bool {
                    foreach ($rows as $m) {
                        $mid = (int) ($m['id'] ?? 0);
                        if ($mid === 0 || isset($seen[$mid])) {
                            continue;
                        }
                        $seen[$mid] = true;
                        $out[] = $m;
                        if (count($out) >= $limit) {
                            return true;
                        }
                    }

                    return false;
                };

                if ($groupId) {
                    $r = $this->getMeals(['page' => 1, 'group_id' => $groupId]);
                    if ($push($r['data'] ?? [])) {
                        return array_slice($out, 0, $limit);
                    }
                    $last = (int) ($r['meta']['lastPage'] ?? 1);
                    if ($last > 1) {
                        $r2 = $this->getMeals(['page' => 2, 'group_id' => $groupId]);
                        if ($push($r2['data'] ?? [])) {
                            return array_slice($out, 0, $limit);
                        }
                    }
                }

                $r = $this->getMeals(['page' => 1]);
                if ($push($r['data'] ?? [])) {
                    return array_slice($out, 0, $limit);
                }
                $last = (int) ($r['meta']['lastPage'] ?? 1);
                if ($last > 1) {
                    $r2 = $this->getMeals(['page' => 2]);
                    $push($r2['data'] ?? []);
                }

                return array_slice($out, 0, $limit);
            }
        );
    }

    /**
     * Transform raw API meal data into the format the frontend expects.
     */
    protected function transformMeal(array $meal): array
    {
        $price = $meal['price'] ?? [];
        $offerPrice = $meal['offer_price'] ?? [];
        $priceAmount = is_array($price) ? ($price['amount'] ?? 0) : $price;
        $offerPriceAmount = is_array($offerPrice) ? ($offerPrice['amount'] ?? 0) : $offerPrice;

        $nutrition = is_array($meal['nutrition'] ?? null) ? $meal['nutrition'] : [];

        $groupId = $meal['group_id'] ?? $meal['menu_id'] ?? data_get($meal, 'group.id');
        if ($groupId !== null && $groupId !== '') {
            $groupId = (int) $groupId;
        } else {
            $groupId = null;
        }

        $mealImage = (string) (
            $meal['image']
            ?? $meal['image_url']
            ?? $meal['cover']
            ?? $meal['thumbnail']
            ?? ''
        );

        $rawCategories = is_array($meal['categories'] ?? null) ? $meal['categories'] : [];
        $categories = array_values(array_filter(array_map(function ($cat) {
            if (! is_array($cat)) {
                return null;
            }

            $name = $cat['name'] ?? '';
            if (is_array($name)) {
                $name = $name[app()->getLocale()] ?? $name['en'] ?? reset($name) ?? '';
            }

            return [
                'id' => (int) ($cat['id'] ?? 0),
                'name' => (string) $name,
                'icon' => (string) ($cat['icon'] ?? ''),
            ];
        }, $rawCategories)));

        $groupName = $meal['group']['name'] ?? $meal['menu']['name'] ?? $meal['group_name'] ?? $meal['menu_name'] ?? '';
        if (is_array($groupName)) {
            $groupName = $groupName[app()->getLocale()] ?? $groupName['en'] ?? reset($groupName) ?? '';
        }

        $tags = is_array($meal['tags'] ?? null) ? $meal['tags'] : [];
        $resolvedTagName = $meal['tag_name'] ?? $meal['tag']['name'] ?? ($tags[0]['name'] ?? '');
        if (is_array($resolvedTagName)) {
            $resolvedTagName = $resolvedTagName[app()->getLocale()] ?? $resolvedTagName['en'] ?? reset($resolvedTagName) ?? '';
        }

        $categoryName = $categories[0]['name'] ?? '';
        if ($categoryName === '') {
            $categoryName = (string) $groupName;
        }

        return [
            'id' => $meal['id'],
            'code' => $meal['code'] ?? $meal['sku'] ?? $meal['product_code'] ?? $meal['id'],
            'name' => $meal['name'] ?? '',
            'description' => $meal['description'] ?? '',
            'image_url' => $this->absoluteMediaUrl($mealImage),
            'price' => (float) $priceAmount,
            'offer_price' => (float) $offerPriceAmount,
            'rate' => $meal['rate'] ?? 0,
            'categories' => $categories,
            'category_name' => $categoryName,
            'tags' => $tags,
            'tag_name' => (string) $resolvedTagName,
            'ingredients' => $meal['ingredients'] ?? [],
            'benefits' => $meal['benefits'] ?? $meal['health_benefits'] ?? '',
            'group_id' => $groupId,
            'group_name' => (string) $groupName,
            'calories' => $this->nutritionFloatAliases($meal, ['calories', 'kcal', 'calorie'], $nutrition),
            'protein' => $this->nutritionFloatAliases($meal, ['protein', 'proteins', 'protein_g'], $nutrition),
            'carbs' => $this->nutritionFloatAliases($meal, ['carbs', 'carb', 'carbohydrates', 'carbohydrate', 'carbs_g'], $nutrition),
            'fat' => $this->nutritionFloatAliases($meal, ['fat', 'fats', 'fat_g', 'fats_g', 'lipids'], $nutrition),
        ];
    }

    /**
     * @param  array<string, mixed>  $nutrition
     */
    protected function nutritionFloat(array $meal, string $key, array $nutrition): ?float
    {
        $v = $meal[$key] ?? $nutrition[$key] ?? null;
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (float) $v;
        }
        if (is_string($v) && preg_match('/([\d.]+)/', $v, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    /**
     * Normalize API pagination keys across snake/camel variants.
     *
     * @param  array<string, mixed>  $meta
     * @return array<string, int>
     */
    protected function normalizePaginationMeta(array $meta, int $fallbackPage = 1): array
    {
        $currentPage = (int) ($meta['currentPage'] ?? $meta['current_page'] ?? $fallbackPage);
        $lastPage = (int) ($meta['lastPage'] ?? $meta['last_page'] ?? 1);
        $perPage = (int) ($meta['perPage'] ?? $meta['per_page'] ?? 0);
        $total = (int) ($meta['total'] ?? 0);

        return [
            'currentPage' => max(1, $currentPage),
            'lastPage' => max(1, $lastPage),
            'perPage' => max(0, $perPage),
            'total' => max(0, $total),
        ];
    }

    /**
     * Read nutrition value across multiple possible API keys/shapes.
     *
     * @param  array<string, mixed>  $meal
     * @param  array<int, string>  $keys
     * @param  array<string, mixed>  $nutrition
     */
    protected function nutritionFloatAliases(array $meal, array $keys, array $nutrition): ?float
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $meal)) {
                $parsed = $this->parseNutritionValue($meal[$key]);
                if ($parsed !== null) {
                    return $parsed;
                }
            }

            if (array_key_exists($key, $nutrition)) {
                $parsed = $this->parseNutritionValue($nutrition[$key]);
                if ($parsed !== null) {
                    return $parsed;
                }
            }
        }

        return null;
    }

    protected function parseNutritionValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        if (is_array($value)) {
            foreach (['amount', 'value', 'total'] as $k) {
                if (array_key_exists($k, $value)) {
                    $nested = $this->parseNutritionValue($value[$k]);
                    if ($nested !== null) {
                        return $nested;
                    }
                }
            }
        }

        if (is_string($value) && preg_match('/([\d.]+)/', $value, $m)) {
            return (float) $m[1];
        }

        return null;
    }

    // ─── Orders ──────────────────────────────────────────────────

    public function getOrders(int $page = 1): array
    {
        return Cache::remember($this->cacheKey("orders_page_{$page}"), 300, function () use ($page) {
            try {
                $response = $this->http()->get("{$this->baseUrl}/orders", ['page' => $page]);
                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                Log::warning('External API /orders failed: '.$e->getMessage());
            }

            return [];
        });
    }

    public function getOrder(int $id): ?array
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/orders/{$id}");
            if ($response->successful()) {
                return $response->json('data');
            }
        } catch (\Exception $e) {
            Log::warning("External API /orders/{$id} failed: ".$e->getMessage());
        }

        return null;
    }

    // ─── Subscriptions ───────────────────────────────────────────

    public function getSubscriptions(int $page = 1, ?string $phone = null): array
    {
        $cacheKey = $this->cacheKey("subscriptions_page_{$page}".($phone ? '_phone_'.md5($phone) : ''));

        return Cache::remember($cacheKey, 300, function () use ($page, $phone) {
            try {
                $params = ['page' => $page];
                if ($phone) {
                    $params['phone'] = $phone;
                }
                $response = $this->http()->get("{$this->baseUrl}/subscriptions", $params);
                if ($response->successful()) {
                    $json = $response->json();
                    $meta = $this->normalizePaginationMeta($json['meta'] ?? [], $page);

                    return [
                        'data' => array_map([$this, 'transformSubscription'], $json['data'] ?? []),
                        'meta' => $meta,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('External API /subscriptions failed: '.$e->getMessage());
            }

            return ['data' => [], 'meta' => ['currentPage' => $page, 'lastPage' => 1]];
        });
    }

    public function getSubscription(int $id): ?array
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/subscriptions/{$id}");
            if ($response->successful()) {
                $data = $response->json('data');

                return $data ? $this->transformSubscription($data) : null;
            }
        } catch (\Exception $e) {
            Log::warning("External API /subscriptions/{$id} failed: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Create a subscription via the backend API.
     */
    public function createSubscription(array $data): array
    {
        try {
            $response = $this->http()->post("{$this->baseUrl}/subscriptions", $data);
            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json('data', [])];
            }

            return ['success' => false, 'message' => $response->json('message', 'Subscription creation failed')];
        } catch (\Exception $e) {
            Log::warning('External API POST /subscriptions failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate subscription pricing via the backend API.
     */
    public function calculateSubscription(array $data): array
    {
        try {
            $response = $this->http()->post("{$this->baseUrl}/subscriptions/calculate", $data);
            if ($response->successful()) {
                return ['success' => true, 'data' => $response->json('data', [])];
            }

            return ['success' => false, 'message' => $response->json('message', 'Calculation failed')];
        } catch (\Exception $e) {
            Log::warning('External API /subscriptions/calculate failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Transform raw subscription data into frontend format.
     */
    protected function transformSubscription(array $subscription): array
    {
        return [
            'id' => $subscription['id'] ?? 0,
            'plan_name' => $subscription['plan']['name'] ?? $subscription['plan_name'] ?? '',
            'plan_image' => $subscription['plan']['image'] ?? $subscription['plan_image'] ?? '',
            'status' => $subscription['status'] ?? 'pending',
            'start_at' => $subscription['start_at'] ?? '',
            'end_at' => $subscription['end_at'] ?? '',
            'total' => $subscription['total'] ?? 0,
            'discount' => $subscription['discount'] ?? 0,
            'tax' => $subscription['tax'] ?? 0,
            'duration_days' => $subscription['duration']['days'] ?? $subscription['duration_days'] ?? 0,
            'calorie_range' => $subscription['calorie']['min_amount'] ?? '',
            'with_weekend' => $subscription['with_weekend'] ?? false,
            'customer_name' => $subscription['customer']['name'] ?? '',
            'customer_phone' => $subscription['customer']['phone'] ?? '',
            'zone_name' => $subscription['zone']['name'] ?? '',
            'days' => $subscription['days'] ?? [],
            'meals_per_day' => $subscription['meals_per_day'] ?? [],
        ];
    }

    // ─── Zones ────────────────────────────────────────────────────

    /**
     * Get delivery zones from API.
     */
    public function getZones(): array
    {
        return Cache::remember($this->cacheKey('zones'), 3600, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/zones");
                if ($response->successful()) {
                    $data = $response->json('data', []);

                    return array_map(function ($zone) {
                        return [
                            'id' => $zone['id'] ?? 0,
                            'name' => $zone['name'] ?? '',
                            'subscription_delivery_price' => (float) ($zone['subscription_delivery_price'] ?? $zone['subscriptionDeliveryPrice'] ?? 0),
                            'order_delivery_price' => (float) ($zone['order_delivery_price'] ?? $zone['orderDeliveryPrice'] ?? 0),
                            'min_order_price' => (float) ($zone['min_order_price'] ?? $zone['minOrderPrice'] ?? 0),
                            'is_active' => $zone['is_active'] ?? $zone['isActive'] ?? true,
                        ];
                    }, $data);
                }
            } catch (\Exception $e) {
                Log::warning('External API /zones failed: '.$e->getMessage());
            }

            return [];
        });
    }

    // ─── Branches ──────────────────────────────────────────────────

    /**
     * Get pickup branches from API.
     */
    public function getBranches(): array
    {
        return Cache::remember($this->cacheKey('branches'), 3600, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/branches");
                if ($response->successful()) {
                    return array_map(function ($branch) {
                        return [
                            'id' => $branch['id'] ?? 0,
                            'name' => $branch['name'] ?? '',
                            'address' => $branch['address'] ?? '',
                            'phone' => $branch['phone'] ?? '',
                            'is_active' => $branch['is_active'] ?? $branch['isActive'] ?? true,
                        ];
                    }, $response->json('data', []));
                }
            } catch (\Exception $e) {
                Log::warning('External API /branches failed: '.$e->getMessage());
            }

            return [];
        });
    }

    // ─── Plan Durations & Calories ────────────────────────────────

    /**
     * Get plan durations from API (available packages for a program).
     */
    public function getPlanDurations(int $planId): array
    {
        return Cache::remember($this->cacheKey("plan_durations_v2_{$planId}"), 3600, function () use ($planId) {
            $rows = $this->fetchPlanDurationsFromEndpoint($planId);
            if ($rows !== []) {
                return $rows;
            }

            return $this->fetchPlanDurationsFromProgramProfile($planId);
        });
    }

    /**
     * Durations safe for POST /subscriptions (endpoint only — no nested profile ids).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAuthoritativePlanDurations(int $planId): array
    {
        return Cache::remember($this->cacheKey("plan_durations_auth_{$planId}"), 3600, function () use ($planId) {
            return $this->fetchPlanDurationsFromEndpoint($planId);
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchPlanDurationsFromEndpoint(int $planId): array
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/programs/{$planId}/durations");
            if ($response->successful()) {
                return array_map(
                    fn (array $d): array => $this->mapPlanDurationRow($d),
                    $response->json('data', [])
                );
            }
        } catch (\Exception $e) {
            Log::warning("External API /programs/{$planId}/durations failed: ".$e->getMessage());
        }

        return [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchPlanDurationsFromProgramProfile(int $planId): array
    {
        $program = $this->getProgram($planId);
        if ($program === null) {
            return [];
        }

        $plans = $program->subscription_plans ?? [];
        if (! is_array($plans)) {
            $plans = json_decode(json_encode($plans), true) ?? [];
        }

        $rows = [];
        foreach ($plans as $plan) {
            if (! is_array($plan)) {
                continue;
            }
            foreach ($plan['durations'] ?? [] as $duration) {
                if (! is_array($duration)) {
                    continue;
                }
                $rows[] = $this->mapPlanDurationRow($duration);
            }
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $d
     * @return array<string, mixed>
     */
    protected function mapPlanDurationRow(array $d): array
    {
        $ofr = $d['offer_price'] ?? $d['offerPrice'] ?? [];
        $pr = (float) ($d['price']['amount'] ?? $d['price'] ?? 0);
        $of = (float) (is_array($ofr) ? ($ofr['amount'] ?? 0) : $ofr);
        $eff = $of > 0 && $of < $pr ? $of : $pr;

        return [
            'id' => $d['id'] ?? 0,
            'days' => (int) ($d['days'] ?? 0),
            'price' => $pr,
            'offer_price' => $of,
            'list_price' => $pr,
            'effective_price' => $eff,
            'has_offer' => $of > 0 && $of < $pr,
            'delivery_price' => (float) ($d['delivery_price']['amount'] ?? $d['delivery_price'] ?? $d['deliveryPrice'] ?? 0),
            'is_default' => $d['is_default'] ?? $d['isDefault'] ?? false,
            'label' => $d['label'] ?? ($d['days'] ?? 0).' '.__('Days'),
            'start_date' => \App\Support\SubscriptionCheckoutPayload::normalizeStartDate(
                (string) ($d['start_date'] ?? $d['startDate'] ?? $d['starts_at'] ?? $d['available_from'] ?? '')
            ) ?: null,
        ];
    }

    /**
     * Get plan calorie options from API.
     */
    public function getPlanCalories(int $planId): array
    {
        return Cache::remember($this->cacheKey("plan_calories_{$planId}"), 3600, function () use ($planId) {
            try {
                $response = $this->http()->get("{$this->baseUrl}/programs/{$planId}/calories");
                if ($response->successful()) {
                    return array_map(function ($c) {
                        $min = (int) ($c['min_amount'] ?? $c['minAmount'] ?? $c['min'] ?? 0);
                        $max = (int) ($c['max_amount'] ?? $c['maxAmount'] ?? $c['max'] ?? 0);
                        $amountStr = trim((string) ($c['amount'] ?? ''));
                        if (($min === 0 || $max === 0) && $amountStr !== '') {
                            $normalized = preg_replace('/\s+/', '', str_replace('–', '-', $amountStr));
                            $parts = preg_split('/\s*-\s*/', $normalized);
                            if (count($parts) === 2) {
                                $min = (int) $parts[0];
                                $max = (int) $parts[1];
                            }
                        }

                        return [
                            'id' => $c['id'] ?? 0,
                            'min_amount' => $min,
                            'max_amount' => $max,
                            'is_default' => $c['is_default'] ?? $c['isDefault'] ?? false,
                            'macros' => $c['macros'] ?? null,
                        ];
                    }, $response->json('data', []));
                }
            } catch (\Exception $e) {
                Log::warning("External API /programs/{$planId}/calories failed: ".$e->getMessage());
            }

            return [];
        });
    }

    // ─── Home (summary data) ─────────────────────────────────────

    public function getHome(): array
    {
        return Cache::remember($this->cacheKey('home'), 1800, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/home");
                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                Log::warning('External API /home failed: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Get shop meal groups from /home API for filter tabs.
     * Returns: [['value' => int, 'name' => string, 'icon' => string], ...]
     */
    public function getShopMealGroups(): array
    {
        $home = $this->getHome();

        return array_map(function ($group) {
            return [
                'value' => $group['value'] ?? $group['id'] ?? 0,
                'name' => $group['name'] ?? '',
                'icon' => $group['icon'] ?? '',
                'count' => (int) ($group['count'] ?? $group['meals_count'] ?? 0),
            ];
        }, $home['shopMealGroups'] ?? []);
    }

    /**
     * GET /meals/filters canonical filter source for store categories.
     *
     * @return array{groups: array, menus: array, tags: array}
     */
    public function getMealFilters(): array
    {
        return Cache::remember($this->cacheKey('meals_filters'), 600, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/meals/filters");
                if ($response->successful()) {
                    $body = $response->json();
                    $payload = $body['data'] ?? $body ?? [];

                    return [
                        'groups' => $this->normalizeFilterBucket(
                            $payload['groups'] ?? $payload['categories'] ?? $payload['shopMealGroups'] ?? []
                        ),
                        'menus' => $this->normalizeFilterBucket($payload['menus'] ?? []),
                        'tags' => $this->normalizeFilterBucket($payload['tags'] ?? []),
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('External API /meals/filters failed: '.$e->getMessage());
            }

            return [
                'groups' => $this->getShopMealGroups(),
                'menus' => [],
                'tags' => [],
            ];
        });
    }

    /**
     * Normalize filter buckets and hide empty categories when count exists.
     */
    protected function normalizeFilterBucket(array $items): array
    {
        $normalized = array_map(function ($item) {
            $count = (int) (
                $item['meals_count']
                ?? $item['count']
                ?? $item['mealsCount']
                ?? $item['programs_count']
                ?? 0
            );

            return [
                'value' => (int) ($item['id'] ?? $item['value'] ?? 0),
                'name' => $item['name'] ?? '',
                'icon' => $item['icon'] ?? $item['image'] ?? '',
                'count' => $count,
            ];
        }, array_values($items));

        $hasReportedCounts = collect($normalized)->contains(fn ($i) => ($i['count'] ?? 0) > 0);

        return $hasReportedCounts
            ? array_values(array_filter($normalized, fn ($i) => ($i['count'] ?? 0) > 0))
            : $normalized;
    }

    // ─── Settings ────────────────────────────────────────────────

    public function getSettings(): array
    {
        return Cache::remember($this->cacheKey('settings'), 3600, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/settings");
                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                Log::warning('External API /settings failed: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Get CMS pages from API (/pages).
     *
     * Returns normalized items:
     * id, slug, title, excerpt, content_html
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPages(): array
    {
        return Cache::remember($this->cacheKey('pages'), 1800, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/pages");
                if ($response->successful()) {
                    $rows = $response->json('data', []);
                    if (! is_array($rows)) {
                        $rows = [];
                    }

                    return array_values(array_filter(array_map(function ($row) {
                        if (! is_array($row)) {
                            return null;
                        }

                        $title = (string) ($row['title'] ?? $row['name'] ?? '');
                        $slug = (string) ($row['slug'] ?? Str::slug($title));
                        $contentRaw = $row['content'] ?? $row['body'] ?? $row['description'] ?? '';

                        return [
                            'id' => (int) ($row['id'] ?? 0),
                            'slug' => $slug,
                            'title' => $title,
                            'excerpt' => trim(strip_tags((string) ($row['excerpt'] ?? ''))),
                            'content_html' => $this->sanitizeHtml((string) $contentRaw),
                        ];
                    }, $rows)));
                }
            } catch (\Exception $e) {
                Log::warning('External API /pages failed: '.$e->getMessage());
            }

            return [];
        });
    }

    /**
     * Get single CMS page from API (/pages/{id}) and normalize.
     *
     * @return array<string, mixed>|null
     */
    public function getPage(int $id): ?array
    {
        try {
            $response = $this->http()->get("{$this->baseUrl}/pages/{$id}");
            if ($response->successful()) {
                $row = $response->json('data', []);
                if (! is_array($row) || empty($row)) {
                    $row = $response->json();
                }
                if (is_array($row) && ! empty($row)) {
                    $title = (string) ($row['title'] ?? $row['name'] ?? '');
                    $slug = (string) ($row['slug'] ?? Str::slug($title));
                    $contentRaw = $row['content'] ?? $row['body'] ?? $row['description'] ?? '';

                    return [
                        'id' => (int) ($row['id'] ?? $id),
                        'slug' => $slug,
                        'title' => $title,
                        'excerpt' => trim(strip_tags((string) ($row['excerpt'] ?? ''))),
                        'content_html' => $this->sanitizeHtml((string) $contentRaw),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning("External API /pages/{$id} failed: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Resolve page by likely slug/title keyword.
     *
     * @param  array<int, string>  $aliases
     * @return array<string, mixed>|null
     */
    public function getPageByAlias(array $aliases): ?array
    {
        $aliases = array_values(array_filter(array_map(static fn ($a) => Str::lower(trim((string) $a)), $aliases)));
        if (empty($aliases)) {
            return null;
        }

        $pages = $this->getPages();
        foreach ($pages as $page) {
            $haystack = Str::lower(implode(' ', [
                (string) ($page['slug'] ?? ''),
                (string) ($page['title'] ?? ''),
                (string) ($page['excerpt'] ?? ''),
            ]));
            foreach ($aliases as $alias) {
                if ($alias !== '' && str_contains($haystack, $alias)) {
                    $id = (int) ($page['id'] ?? 0);
                    if ($id > 0) {
                        $fresh = $this->getPage($id);
                        if ($fresh) {
                            return $fresh;
                        }
                    }

                    return $page;
                }
            }
        }

        return null;
    }

    /**
     * Get common questions from API (/commonQuestions).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCommonQuestions(): array
    {
        return Cache::remember($this->cacheKey('common_questions'), 1800, function () {
            try {
                $response = $this->http()->get("{$this->baseUrl}/commonQuestions");
                if ($response->successful()) {
                    $rows = $response->json('data', []);
                    if (! is_array($rows)) {
                        $rows = [];
                    }

                    return array_values(array_filter(array_map(function ($row) {
                        if (! is_array($row)) {
                            return null;
                        }

                        $question = trim((string) ($row['question'] ?? $row['title'] ?? ''));
                        $answerRaw = $row['answer'] ?? $row['content'] ?? $row['description'] ?? '';
                        $category = (string) ($row['category'] ?? $row['section'] ?? $row['group'] ?? __('General'));
                        if ($question === '') {
                            return null;
                        }

                        return [
                            'id' => (int) ($row['id'] ?? 0),
                            'question' => $question,
                            'answer_html' => $this->sanitizeHtml((string) $answerRaw),
                            'answer_text' => trim(strip_tags((string) $answerRaw)),
                            'category' => $category !== '' ? $category : __('General'),
                        ];
                    }, $rows)));
                }
            } catch (\Exception $e) {
                Log::warning('External API /commonQuestions failed: '.$e->getMessage());
            }

            return [];
        });
    }

    protected function sanitizeHtml(string $html): string
    {
        $clean = trim($html);
        if ($clean === '') {
            return '';
        }

        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $clean) ?? $clean;
        $clean = preg_replace('/\son\w+=(["\']).*?\1/iu', '', $clean) ?? $clean;
        $clean = preg_replace('/javascript:/iu', '', $clean) ?? $clean;

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h1><h2><h3><h4><blockquote><span><div>';

        return trim(strip_tags($clean, $allowed));
    }

    /**
     * POST /addresses — same auth as other external calls (EXTERNAL_API_TOKEN).
     * Expected form fields: title, longitude, latitude, description, type, district_id, pickup_type.
     *
     * @param  array<string, string>  $fields
     * @return array<string, mixed>
     */
    public function createAddress(array $fields): array
    {
        if (! $this->token) {
            Log::info('ExternalDataService::createAddress skipped: EXTERNAL_API_TOKEN not set');

            return ['success' => false, 'skipped' => true];
        }

        try {
            $response = $this->http()->asForm()->post("{$this->baseUrl}/addresses", $fields);
            $json = $response->json() ?? [];
            $json['_http_ok'] = $response->successful();

            if (! $response->successful()) {
                Log::warning('ExternalDataService::createAddress HTTP error', [
                    'status' => $response->status(),
                    'body' => Str::limit($response->body(), 500),
                ]);
                $json['message'] = $json['message'] ?? __('address.save_failed');
            }

            return $json;
        } catch (\Throwable $e) {
            Log::error('ExternalDataService::createAddress failed', ['error' => $e->getMessage()]);

            return ['_http_ok' => false, 'message' => __('address.save_failed')];
        }
    }

    // ─── Cache ───────────────────────────────────────────────────

    public function clearCache(): void
    {
        $baseKeys = [
            'programs',
            'program_categories',
            'home',
            'settings',
            'all_meals',
            'meals_filters',
            'zones',
        ];

        foreach (['en', 'ar'] as $locale) {
            foreach ($baseKeys as $key) {
                Cache::forget("{$locale}_{$key}");
            }
            for ($i = 1; $i <= 20; $i++) {
                Cache::forget("{$locale}_orders_page_{$i}");
                Cache::forget("{$locale}_subscriptions_page_{$i}");
            }
        }
    }
}
