<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealGroupResource\Pages;

use App\Filament\Resources\MealGroupResource;
use Filament\Resources\Pages\EditRecord;

class EditMealGroup extends EditRecord
{
    protected static string $resource = MealGroupResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['name'] = $this->getRecord()->getTranslations('name');
        return $data;
    }
}
