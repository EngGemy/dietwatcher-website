<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaq extends CreateRecord
{
    protected static string $resource = FaqResource::class;

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
