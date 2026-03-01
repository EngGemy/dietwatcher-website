<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqCategoryResource\Pages;

use App\Filament\Resources\FaqCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaqCategory extends CreateRecord
{
    protected static string $resource = FaqCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (isset($data['translations'])) {
            foreach ($data['translations'] as $locale => $fields) {
                $data[$locale] = $fields;
            }
            unset($data['translations']);
        }
        return $data;
    }
}
