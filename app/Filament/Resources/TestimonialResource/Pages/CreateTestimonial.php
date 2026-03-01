<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestimonialResource\Pages;

use App\Filament\Resources\TestimonialResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestimonial extends CreateRecord
{
    protected static string $resource = TestimonialResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Transform translations.locale.field to locale.field for Astrotomic mass assignment
        if (isset($data['translations'])) {
            foreach ($data['translations'] as $locale => $fields) {
                $data[$locale] = $fields;
            }
            unset($data['translations']);
        }
        return $data;
    }
}
