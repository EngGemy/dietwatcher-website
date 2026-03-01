<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalDataService
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.external_api.url', 'https://diet-watchers-stage-fbofszkn.on-forge.com/api'), '/');
        $this->token = config('services.external_api.token');
    }

    protected function http(): \Illuminate\Http\Client\PendingRequest
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
     * Generate a locale-aware cache key.
     */
    protected function cacheKey(string $key): string
    {
        return app()->getLocale() . '_' . $key;
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
                Log::warning('External API /programs failed: ' . $e->getMessage());
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
            if ($response->successful() && !isset($response->json()['exception'])) {
                $raw = $response->json('data');
                if ($raw) {
                    return (object) $this->transformProgram($raw);
                }
            }
        } catch (\Exception $e) {
            Log::warning("External API /programs/{$id} failed: " . $e->getMessage());
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
    protected function transformProgram(array $program): array
    {
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
                $calorieOptions[] = ['range' => "{$calMin}-" . ($calMin + $step), 'label' => "{$calMin}-" . ($calMin + $step)];
                $mid = $calMin + $step + 1;
                $calorieOptions[] = ['range' => "{$mid}-" . ($mid + $step), 'label' => "{$mid}-" . ($mid + $step)];
                $calorieOptions[] = ['range' => ($mid + $step + 1) . "-{$calMax}", 'label' => ($mid + $step + 1) . "-{$calMax}"];
            } else {
                $calorieOptions[] = ['range' => "{$calMin}-{$calMax}", 'label' => "{$calMin}-{$calMax}"];
            }
        } elseif ($calMax) {
            $calorieOptions[] = ['range' => "{$calMax}", 'label' => "{$calMax} kcal"];
        }

        return [
            'id' => $program['id'],
            'name' => $program['name'] ?? '',
            'description' => $program['description'] ?? '',
            'image_url' => $program['image'] ?? '',
            'price' => (int) $priceAmount,
            'offer_price' => (int) $offerPriceAmount,
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
                    if (!empty($data)) {
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
                                'image_url' => $cat['cover'] ?? $cat['image'] ?? $cat['image_url'] ?? '',
                                'badge' => $cat['badge'] ?? null,
                                'programs_count' => $cat['programsCount'] ?? 0,
                            ];
                        }, $data);
                    }
                }
            } catch (\Exception $e) {
                Log::warning('External API /program-categories failed: ' . $e->getMessage());
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

        if (!empty($categories)) {
            return $categories;
        }

        // Fallback: use programs as display categories for homepage
        return array_map(fn(array $p) => [
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
            $programs = array_filter($programs, fn($p) => ($p['category_id'] ?? null) == $categoryId);
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
            Log::warning("External API /programs/get-meals/{$id} failed: " . $e->getMessage());
        }
        return [];
    }

    // ─── Meals ───────────────────────────────────────────────────

    /**
     * Get meals from /meals API with full filter support.
     *
     * @param  array  $filters  Supported: page, group_id, menu_id, tags (array of tag IDs)
     * @return array{data: array, meta: array}
     */
    public function getMeals(array $filters = []): array
    {
        $page = (int) ($filters['page'] ?? 1);
        $groupId = $filters['group_id'] ?? null;
        $menuId = $filters['menu_id'] ?? null;
        $tags = $filters['tags'] ?? [];

        $cacheKey = $this->cacheKey('meals_' . md5(json_encode($filters)));

        return Cache::remember($cacheKey, 300, function () use ($page, $groupId, $menuId, $tags) {
            try {
                $params = ['page' => $page];
                if ($groupId) {
                    $params['group_id'] = $groupId;
                }
                if ($menuId) {
                    $params['menu_id'] = $menuId;
                }
                if (!empty($tags)) {
                    $params['tags'] = $tags;
                }

                $response = $this->http()->get("{$this->baseUrl}/meals", $params);
                if ($response->successful()) {
                    $json = $response->json();
                    return [
                        'data' => array_map([$this, 'transformMeal'], $json['data'] ?? []),
                        'meta' => $json['meta'] ?? ['currentPage' => $page, 'lastPage' => 1],
                    ];
                }
            } catch (\Exception $e) {
                Log::warning('External API /meals failed: ' . $e->getMessage());
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
        $cacheKey = $this->cacheKey('all_meals' . ($groupId ? "_group_{$groupId}" : ''));

        return Cache::remember($cacheKey, 300, function () use ($groupId) {
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
                        $lastPage = (int) ($json['meta']['lastPage'] ?? 1);
                    } else {
                        break;
                    }
                } catch (\Exception $e) {
                    Log::warning("External API /meals (page {$page}) failed: " . $e->getMessage());
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
     * Transform raw API meal data into the format the frontend expects.
     */
    protected function transformMeal(array $meal): array
    {
        $price = $meal['price'] ?? [];
        $offerPrice = $meal['offer_price'] ?? [];
        $priceAmount = is_array($price) ? ($price['amount'] ?? 0) : $price;
        $offerPriceAmount = is_array($offerPrice) ? ($offerPrice['amount'] ?? 0) : $offerPrice;

        return [
            'id' => $meal['id'],
            'name' => $meal['name'] ?? '',
            'description' => $meal['description'] ?? '',
            'image_url' => $meal['image'] ?? '',
            'price' => (float) $priceAmount,
            'offer_price' => (float) $offerPriceAmount,
            'rate' => $meal['rate'] ?? 0,
            'categories' => $meal['categories'] ?? [],
            'tags' => $meal['tags'] ?? [],
            'tag_name' => $meal['tags'][0]['name'] ?? '',
            'ingredients' => $meal['ingredients'] ?? [],
        ];
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
                Log::warning('External API /orders failed: ' . $e->getMessage());
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
            Log::warning("External API /orders/{$id} failed: " . $e->getMessage());
        }
        return null;
    }

    // ─── Subscriptions ───────────────────────────────────────────

    public function getSubscriptions(int $page = 1): array
    {
        return Cache::remember($this->cacheKey("subscriptions_page_{$page}"), 300, function () use ($page) {
            try {
                $response = $this->http()->get("{$this->baseUrl}/subscriptions", ['page' => $page]);
                if ($response->successful()) {
                    return $response->json('data', []);
                }
            } catch (\Exception $e) {
                Log::warning('External API /subscriptions failed: ' . $e->getMessage());
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
                Log::warning('External API /home failed: ' . $e->getMessage());
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
            ];
        }, $home['shopMealGroups'] ?? []);
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
                Log::warning('External API /settings failed: ' . $e->getMessage());
            }
            return [];
        });
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
