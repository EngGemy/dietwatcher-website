<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanCategoryResource\Pages;

use App\Filament\Resources\PlanCategoryResource;
use App\Models\PlanCategoryTranslation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditPlanCategory extends EditRecord
{
    protected static string $resource = PlanCategoryResource::class;

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
            PlanCategoryTranslation::query()->updateOrCreate(
                [
                    'plan_category_id' => $this->getRecord()->getKey(),
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
        return __('admin.plan_categories.pages.edit');
    }
}
