<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealTagResource\Pages;

use App\Filament\Resources\MealTagResource;
use Filament\Resources\Pages\ListRecords;

class ListMealTags extends ListRecords
{
    protected static string $resource = MealTagResource::class;
}
