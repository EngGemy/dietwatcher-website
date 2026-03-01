<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealTagResource\Pages;

use App\Filament\Resources\MealTagResource;
use Filament\Resources\Pages\EditRecord;

class EditMealTag extends EditRecord
{
    protected static string $resource = MealTagResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['name'] = $this->getRecord()->getTranslations('name');
        return $data;
    }
}
