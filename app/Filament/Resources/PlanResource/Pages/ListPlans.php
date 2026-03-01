<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Resources\Pages\ListRecords;

class ListPlans extends ListRecords
{
    protected static string $resource = PlanResource::class;

    public function getTitle(): string
    {
        return __('admin.plans.pages.list');
    }
}
