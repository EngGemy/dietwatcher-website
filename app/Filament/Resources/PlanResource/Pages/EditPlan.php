<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\PlanTranslation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $locales = array_keys(config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']));
        $data['translations'] = [];
        foreach ($locales as $locale) {
            $trans = $record->translate($locale);
            $data['translations'][$locale] = [
                'name' => $trans?->name ?? '',
                'subtitle' => $trans?->subtitle ?? '',
                'description' => $trans?->description ?? '',
                'ingredients' => $trans?->ingredients ?? '',
                'benefits' => $trans?->benefits ?? '',
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $translations = Arr::pull($data, 'translations', []);
        foreach ($translations as $locale => $attrs) {
            PlanTranslation::query()->updateOrCreate(
                [
                    'plan_id' => $this->getRecord()->getKey(),
                    'locale' => $locale,
                ],
                [
                    'name' => $attrs['name'] ?? '',
                    'subtitle' => $attrs['subtitle'] ?? null,
                    'description' => $attrs['description'] ?? null,
                    'ingredients' => $attrs['ingredients'] ?? null,
                    'benefits' => $attrs['benefits'] ?? null,
                ]
            );
        }

        return $data;
    }

    public function getTitle(): string
    {
        return __('admin.plans.pages.edit');
    }
}
