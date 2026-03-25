<?php

declare(strict_types=1);

namespace App\Livewire\Meals;

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
        $this->groups = $service->getShopMealGroups();
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

            $result = $service->getMeals($filters);
            $meals = $result['data'];
            $this->lastPage = (int) ($result['meta']['lastPage'] ?? 1);
        }

        // Pass current cart state so each card can show its qty
        $cartItems = session()->get('cart', []);

        return view('livewire.meals.meals-list', [
            'meals'     => $meals,
            'cartItems' => $cartItems,
        ]);
    }
}
