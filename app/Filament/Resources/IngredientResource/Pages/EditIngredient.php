<?php

declare(strict_types=1);

namespace App\Filament\Resources\IngredientResource\Pages;

use App\Filament\Resources\IngredientResource;
use Filament\Resources\Pages\EditRecord;

class EditIngredient extends EditRecord
{
    protected static string $resource = IngredientResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['name'] = $this->getRecord()->getTranslations('name');
        return $data;
    }
}
