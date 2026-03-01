<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Services\ExternalDataService;
use Filament\Pages\Page;

class ExternalProgramCategories extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected string $view = 'filament.pages.external-program-categories';

    protected static ?int $navigationSort = 10;

    public static function getNavigationGroup(): ?string
    {
        return 'External Data';
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.external.categories.navigation_label');
    }

    public function getTitle(): string
    {
        return __('admin.external.categories.title');
    }

    public function getCategories(): array
    {
        try {
            return app(ExternalDataService::class)->getCategories();
        } catch (\Exception $e) {
            return [];
        }
    }
}
