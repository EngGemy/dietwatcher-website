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
    public ?int $selectedMenu = null;
    /** @var int[] */
    public array $selectedTags = [];
    public int $currentPage = 1;

    public int $lastPage = 1;

    public string $search = '';

    /** Filter buckets fetched from /meals/filters (only non-empty are kept) */
    public array $groups = [];
    public array $menus  = [];
    public array $tags   = [];

    public function mount(): void
    {
        $service = app(ExternalDataService::class);

        // /meals/filters is the canonical source — falls back to /home groups
        // inside the service if the endpoint is not available yet.
        $filters = $service->getMealFilters();

        $this->groups = $filters['groups'] ?? [];
        $this->menus  = $filters['menus']  ?? [];
        $this->tags   = $filters['tags']   ?? [];
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

    public function filterByMenu(?int $menuId): void
    {
        $this->selectedMenu = $menuId;
        $this->currentPage = 1;
    }

    public function toggleTag(int $tagId): void
    {
        if (in_array($tagId, $this->selectedTags, true)) {
            $this->selectedTags = array_values(array_diff($this->selectedTags, [$tagId]));
        } else {
            $this->selectedTags[] = $tagId;
        }
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
            $allMeals = $service->getAllMeals($this->selectedGroup);

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
            if ($this->selectedMenu) {
                $filters['menu_id'] = $this->selectedMenu;
            }
            if (! empty($this->selectedTags)) {
                $filters['tags'] = $this->selectedTags;
            }

            $result = $service->getMeals($filters);
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
