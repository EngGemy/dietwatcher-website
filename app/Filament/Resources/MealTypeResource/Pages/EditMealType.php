<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealTypeResource\Pages;

use App\Filament\Resources\MealTypeResource;
use App\Models\MealTypeTranslation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditMealType extends EditRecord
{
    protected static string $resource = MealTypeResource::class;

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $locales = array_keys(config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']));
        $data['translations'] = [];
        foreach ($locales as $locale) {
            $trans = $record->translate($locale);
            $data['translations'][$locale] = [
                'name' => $trans?->name ?? '',
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $translations = Arr::pull($data, 'translations', []);
        foreach ($translations as $locale => $attrs) {
            MealTypeTranslation::query()->updateOrCreate(
                [
                    'meal_type_id' => $this->getRecord()->getKey(),
                    'locale' => $locale,
                ],
                [
                    'name' => $attrs['name'] ?? '',
                ]
            );
        }

        return $data;
    }

    public function getTitle(): string
    {
        return __('admin.meal_types.pages.edit');
    }
}
