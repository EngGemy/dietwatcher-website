<?php

declare(strict_types=1);

namespace App\Livewire\Meals;

use App\Livewire\Cart\CartManager;
use App\Services\ExternalDataService;
use Livewire\Attributes\On;
use Livewire\Component;

class MealsList extends Component
{
    public ?int $selectedGroup = null;

    public int $currentPage = 1;

    public int $lastPage = 1;

    public string $search = '';

    /** @var list<int> */
    public array $selectedTagIds = [];

    /** Meal groups from /meals/filters for the filter bar */
    public array $groups = [];

    public function mount(): void
    {
        $service = app(ExternalDataService::class);
        $filters = $service->getMealFilters();
        $groups = $filters['groups'] ?? [];

        $this->hydrateTagIdsFromRequest();

        $hasEmptyReportedCount = collect($groups)->contains(
            static fn (array $g): bool => (int) ($g['count'] ?? 0) === 0
        );

        if ($groups !== [] && ! $hasEmptyReportedCount) {
            $this->groups = array_values(array_filter(
                $groups,
                static fn (array $group): bool => (int) ($group['value'] ?? 0) > 0
            ));
        } else {
            $this->groups = $this->resolveGroupsWithMealPresence($service, $groups);
        }

        $groupIdsInTabs = array_map(static fn (array $group): int => (int) ($group['value'] ?? 0), $this->groups);
        if ($this->selectedGroup !== null && ! in_array($this->selectedGroup, $groupIdsInTabs, true)) {
            $this->selectedGroup = null;
        }
    }

    /** Tag filter is driven only from meal detail links (?tag= or ?tags[]=). */
    private function hydrateTagIdsFromRequest(): void
    {
        $ids = [];
        $single = request()->query('tag');
        if ($single !== null && $single !== '') {
            $id = (int) $single;
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        $multi = request()->query('tags');
        if (is_array($multi)) {
            foreach ($multi as $v) {
                $id = (int) $v;
                if ($id > 0) {
                    $ids[] = $id;
                }
            }
        }
        $this->selectedTagIds = array_values(array_unique($ids));
        sort($this->selectedTagIds);
    }

    public function clearTagFilterFromQuery(): void
    {
        $this->selectedTagIds = [];
        $this->currentPage = 1;
        $target = request()->routeIs('meals.index') ? route('meals.index') : route('store.index');
        $this->redirect($target);
    }

    /**
     * When the API omits reliable per-group counts, scan meals (cached) or probe per group.
     *
     * @param  array<int, array<string, mixed>>  $groups
     * @return array<int, array<string, mixed>>
     */
    private function resolveGroupsWithMealPresence(ExternalDataService $service, array $groups): array
    {
        $allMeals = $service->getAllMeals();
        $groupIdsWithMeals = [];
        foreach ($allMeals as $meal) {
            $gid = (int) ($meal['group_id'] ?? 0);
            if ($gid > 0) {
                $groupIdsWithMeals[$gid] = true;
            }
        }

        $groupPresenceByApi = [];
        if ($groupIdsWithMeals === []) {
            foreach ($groups as $group) {
                $groupId = (int) ($group['value'] ?? 0);
                if ($groupId <= 0) {
                    continue;
                }
                $probeByGroup = $service->getMeals(['page' => 1, 'group_id' => $groupId]);
                $hasRows = ! empty($probeByGroup['data'] ?? []);
                if (! $hasRows) {
                    $probeByMenu = $service->getMeals(['page' => 1, 'menu_id' => $groupId]);
                    $hasRows = ! empty($probeByMenu['data'] ?? []);
                }
                $groupPresenceByApi[$groupId] = $hasRows;
            }
        }

        return array_values(array_filter($groups, static function (array $group) use ($groupIdsWithMeals, $groupPresenceByApi): bool {
            $groupId = (int) ($group['value'] ?? 0);
            $reportedCount = (int) ($group['count'] ?? 0);

            if ($groupId <= 0) {
                return false;
            }

            if ($reportedCount > 0) {
                return true;
            }

            if ($groupIdsWithMeals !== []) {
                return isset($groupIdsWithMeals[$groupId]);
            }

            if (array_key_exists($groupId, $groupPresenceByApi)) {
                return (bool) $groupPresenceByApi[$groupId];
            }

            return true;
        }));
    }

    /** Re-render when cart changes so card qty controls stay in sync */
    #[On('cart-updated')]
    public function onCartUpdated(): void
    {
        // Render is called automatically; session is read fresh in render()
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
    }

    public function filterByGroup(?int $groupId): void
    {
        $this->selectedGroup = $groupId;
        $this->currentPage = 1;
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->lastPage) {
            $this->currentPage++;
        }
    }

    public function prevPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
        }
    }

    public function goToPage(int $page): void
    {
        $this->currentPage = max(1, min($page, $this->lastPage));
    }

    /**
     * @return array{meals: array<int, array<string, mixed>>, lastPage: int, clientSearchFallback: bool}
     */
    private function fetchMealsPage(ExternalDataService $service): array
    {
        $searchTrim = trim($this->search);
        $tagIds = $this->selectedTagIds;

        $filters = [
            'page' => $this->currentPage,
        ];
        if ($this->selectedGroup) {
            $filters['group_id'] = $this->selectedGroup;
        }
        if ($tagIds !== []) {
            $filters['tags'] = $tagIds;
        }
        if ($searchTrim !== '') {
            $filters['search'] = $searchTrim;
        }

        $result = $service->getMeals($filters);
        if (
            empty($result['data'] ?? [])
            && $this->currentPage === 1
            && $searchTrim === ''
            && $tagIds === []
        ) {
            $service->clearCatalogCacheEntry('meals_'.md5(json_encode($filters)));
            $result = $service->getMeals($filters);
        }

        if ($this->selectedGroup && empty($result['data'] ?? [])) {
            $menuFilters = array_merge($filters, ['menu_id' => $this->selectedGroup]);
            unset($menuFilters['group_id']);
            $menuResult = $service->getMeals($menuFilters);
            if (! empty($menuResult['data'] ?? [])) {
                $result = $menuResult;
            }
        }

        $meals = $result['data'] ?? [];
        $lastPage = (int) ($result['meta']['lastPage'] ?? 1);
        $total = (int) ($result['meta']['total'] ?? count($meals));

        if (
            $searchTrim !== ''
            && $this->currentPage === 1
            && $meals === []
            && $total === 0
        ) {
            $allMeals = $this->selectedGroup
                ? $service->getAllMeals($this->selectedGroup)
                : $service->getAllMeals();

            if ($this->selectedGroup && $allMeals === []) {
                $allMeals = array_values(array_filter(
                    $service->getAllMeals(),
                    fn ($meal) => (int) ($meal['group_id'] ?? 0) === (int) $this->selectedGroup
                ));
            }

            if ($tagIds !== []) {
                $tagSet = array_flip($tagIds);
                $allMeals = array_values(array_filter($allMeals, function ($meal) use ($tagSet): bool {
                    foreach ($meal['tags'] ?? [] as $t) {
                        $tid = is_array($t) ? (int) ($t['id'] ?? 0) : 0;
                        if ($tid > 0 && isset($tagSet[$tid])) {
                            return true;
                        }
                    }

                    return false;
                }));
            }

            $query = mb_strtolower($searchTrim);
            $filtered = array_values(array_filter($allMeals, function ($meal) use ($query) {
                return str_contains(mb_strtolower((string) ($meal['name'] ?? '')), $query)
                    || str_contains(mb_strtolower((string) ($meal['description'] ?? '')), $query)
                    || str_contains(mb_strtolower((string) ($meal['tag_name'] ?? '')), $query);
            }));

            return [
                'meals' => $filtered,
                'lastPage' => 1,
                'clientSearchFallback' => true,
            ];
        }

        return [
            'meals' => $meals,
            'lastPage' => max(1, $lastPage),
            'clientSearchFallback' => false,
        ];
    }

    public function render()
    {
        $service = app(ExternalDataService::class);

        $payload = $this->fetchMealsPage($service);
        $this->lastPage = $payload['lastPage'];

        $rawCart = session()->get(CartManager::SESSION_MARKET, []);
        $cartItems = array_filter(
            $rawCart,
            static fn ($key) => is_string($key) && str_starts_with($key, 'meal_'),
            ARRAY_FILTER_USE_KEY
        );

        return view('livewire.meals.meals-list', [
            'meals' => $payload['meals'],
            'cartItems' => $cartItems,
            'clientSearchFallback' => $payload['clientSearchFallback'],
        ]);
    }
}
