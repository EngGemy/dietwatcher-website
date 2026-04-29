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

    /** Meal groups from /home API for the filter bar */
    public array $groups = [];

    public function mount(): void
    {
        $service = app(ExternalDataService::class);
        $filters = $service->getMealFilters();
        $groups = $filters['groups'] ?? [];

        // Keep categories visible, but hide only proven-empty ones.
        // Priority:
        // 1) If API reports count > 0, keep it.
        // 2) Otherwise, verify by scanning meals group_id.
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

        $this->groups = array_values(array_filter($groups, static function (array $group) use ($groupIdsWithMeals, $groupPresenceByApi): bool {
            $groupId = (int) ($group['value'] ?? 0);
            $reportedCount = (int) ($group['count'] ?? 0);

            if ($groupId <= 0) {
                return false;
            }

            if ($reportedCount > 0) {
                return true;
            }

            // Only hide when we can prove empty from meals payload.
            if ($groupIdsWithMeals !== []) {
                return isset($groupIdsWithMeals[$groupId]);
            }

            // If group mapping is unavailable, verify with per-group API probe.
            if (array_key_exists($groupId, $groupPresenceByApi)) {
                return (bool) $groupPresenceByApi[$groupId];
            }

            return true;
        }));

        $groupIdsInTabs = array_map(static fn (array $group): int => (int) ($group['value'] ?? 0), $this->groups);
        if ($this->selectedGroup !== null && !in_array($this->selectedGroup, $groupIdsInTabs, true)) {
            $this->selectedGroup = null;
        }
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

    public function render()
    {
        $service = app(ExternalDataService::class);

        if ($this->search !== '') {
            $allMeals = $this->selectedGroup
                ? $service->getAllMeals($this->selectedGroup)
                : $service->getAllMeals();

            // Fallback: some API setups expect menu_id instead of group_id.
            if ($this->selectedGroup && $allMeals === []) {
                $allMeals = array_values(array_filter(
                    $service->getAllMeals(),
                    fn ($meal) => (int) ($meal['group_id'] ?? 0) === (int) $this->selectedGroup
                ));
            }

            $query = mb_strtolower($this->search);
            $meals = array_values(array_filter($allMeals, function ($meal) use ($query) {
                return str_contains(mb_strtolower($meal['name']), $query)
                    || str_contains(mb_strtolower($meal['description'] ?? ''), $query)
                    || str_contains(mb_strtolower($meal['tag_name'] ?? ''), $query);
            }));

            $this->lastPage = 1;
        } else {
            $filters = ['page' => $this->currentPage];

            if ($this->selectedGroup) {
                $filters['group_id'] = $this->selectedGroup;
            }

            $result = $service->getMeals($filters);
            if ($this->selectedGroup && empty($result['data'] ?? [])) {
                $menuFilters = ['page' => $this->currentPage, 'menu_id' => $this->selectedGroup];
                $menuResult = $service->getMeals($menuFilters);
                if (! empty($menuResult['data'] ?? [])) {
                    $result = $menuResult;
                }
            }
            $meals = $result['data'];
            $this->lastPage = (int) ($result['meta']['lastPage'] ?? 1);
        }

        $rawCart = session()->get(CartManager::SESSION_MARKET, []);
        $cartItems = array_filter(
            $rawCart,
            static fn ($key) => is_string($key) && str_starts_with($key, 'meal_'),
            ARRAY_FILTER_USE_KEY
        );

        return view('livewire.meals.meals-list', [
            'meals' => $meals,
            'cartItems' => $cartItems,
        ]);
    }
}
