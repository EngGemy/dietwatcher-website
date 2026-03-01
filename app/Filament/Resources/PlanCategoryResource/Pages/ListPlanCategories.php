<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanCategoryResource\Pages;

use App\Filament\Resources\PlanCategoryResource;
use Filament\Resources\Pages\ListRecords;

class ListPlanCategories extends ListRecords
{
    protected static string $resource = PlanCategoryResource::class;

    public function getTitle(): string
    {
        return __('admin.plan_categories.pages.list');
    }
}
