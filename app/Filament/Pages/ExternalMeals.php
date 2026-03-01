<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\ExternalDataService;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ExternalMeals extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-fire';

    protected string $view = 'filament.pages.external-meals';

    protected static ?int $navigationSort = 30;

    #[Url]
    public int $page = 1;

    #[Url]
    public ?int $groupId = null;

    public ?array $selectedMeal = null;

    public static function getNavigationGroup(): ?string
    {
        return 'External Data';
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.external.meals.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.external.meals.title');
    }

    public function getMealsData(): array
    {
        try {
            $filters = ['page' => $this->page];
            if ($this->groupId) {
                $filters['group_id'] = $this->groupId;
            }
            return app(ExternalDataService::class)->getMeals($filters);
        } catch (\Exception $e) {
            return ['data' => [], 'meta' => ['currentPage' => 1, 'lastPage' => 1]];
        }
    }

    public function getMealGroups(): array
    {
        try {
            return app(ExternalDataService::class)->getShopMealGroups();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function filterByGroup(?int $groupId): void
    {
        $this->groupId = $groupId;
        $this->page = 1;
    }

    public function selectMeal(int $index): void
    {
        $mealsData = $this->getMealsData();
        $meals = $mealsData['data'] ?? [];
        $this->selectedMeal = $meals[$index] ?? null;
    }

    public function closeDetail(): void
    {
        $this->selectedMeal = null;
    }

    public function goToPage(int $page): void
    {
        $this->page = $page;
    }

    public function previousPage(): void
    {
        if ($this->page > 1) {
            $this->page--;
        }
    }

    public function nextPage(): void
    {
        $this->page++;
    }
}
