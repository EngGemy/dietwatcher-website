<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealTypeResource\Pages;

use App\Filament\Resources\MealTypeResource;
use Filament\Resources\Pages\ListRecords;

class ListMealTypes extends ListRecords
{
    protected static string $resource = MealTypeResource::class;

    public function getTitle(): string
    {
        return __('admin.meal_types.pages.list');
    }
}
