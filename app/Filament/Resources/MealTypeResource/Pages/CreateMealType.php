<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealTypeResource\Pages;

use App\Filament\Resources\MealTypeResource;
use App\Models\MealTypeTranslation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateMealType extends CreateRecord
{
    protected static string $resource = MealTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->translationsData = Arr::pull($data, 'translations', []);

        return $data;
    }

    /** @var array<string, array<string, mixed>> */
    protected array $translationsData = [];

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        foreach ($this->translationsData as $locale => $attrs) {
            if (empty($attrs['name'] ?? null)) {
                continue;
            }
            MealTypeTranslation::query()->updateOrCreate(
                [
                    'meal_type_id' => $record->getKey(),
                    'locale' => $locale,
                ],
                [
                    'name' => $attrs['name'] ?? '',
                ]
            );
        }
    }

    public function getTitle(): string
    {
        return __('admin.meal_types.pages.create');
    }
}
