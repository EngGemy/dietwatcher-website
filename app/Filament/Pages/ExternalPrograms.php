<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\ExternalDataService;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class ExternalPrograms extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected string $view = 'filament.pages.external-programs';

    protected static ?int $navigationSort = 20;

    #[Url]
    public ?int $selectedCategory = null;

    public ?array $selectedProgram = null;

    public static function getNavigationGroup(): ?string
    {
        return 'External Data';
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.external.programs.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.external.programs.title');
    }

    public function filterByCategory(?int $categoryId = null): void
    {
        if ($categoryId === null || $this->selectedCategory === $categoryId) {
            $this->selectedCategory = null;
        } else {
            $this->selectedCategory = $categoryId;
        }
    }

    public function getPrograms(): array
    {
        try {
            return app(ExternalDataService::class)->getPrograms();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getFilteredPrograms(): array
    {
        if (empty($this->selectedCategory)) {
            return $this->getPrograms();
        }

        try {
            return app(ExternalDataService::class)->getPrograms($this->selectedCategory);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getCategories(): array
    {
        try {
            return app(ExternalDataService::class)->getCategories();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function selectProgram(int $index): void
    {
        $programs = $this->getFilteredPrograms();
        $this->selectedProgram = $programs[$index] ?? null;
    }

    public function closeDetail(): void
    {
        $this->selectedProgram = null;
    }
}
