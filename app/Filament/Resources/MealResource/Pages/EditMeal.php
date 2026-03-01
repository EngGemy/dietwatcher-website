<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealResource\Pages;

use App\Filament\Resources\MealResource;
use Filament\Resources\Pages\EditRecord;

class EditMeal extends EditRecord
{
    protected static string $resource = MealResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        $data['name'] = $record->getTranslations('name');
        $data['description'] = $record->getTranslations('description');

        return $data;
    }
}
