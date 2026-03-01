<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeatureResource\Pages;

use App\Filament\Resources\FeatureResource;
use App\Models\Content\Feature;
use App\Models\Content\FeatureTranslation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditFeature extends EditRecord
{
    protected static string $resource = FeatureResource::class;

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $locales = array_keys(config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']));
        $data['translations'] = [];
        foreach ($locales as $locale) {
            $trans = $record->translate($locale);
            $data['translations'][$locale] = [
                'title' => $trans?->title ?? '',
                'description' => $trans?->description ?? '',
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $translations = Arr::pull($data, 'translations', []);
        foreach ($translations as $locale => $attrs) {
            FeatureTranslation::query()->updateOrCreate(
                [
                    'feature_id' => $this->getRecord()->getKey(),
                    'locale' => $locale,
                ],
                [
                    'title' => $attrs['title'] ?? '',
                    'description' => $attrs['description'] ?? null,
                ]
            );
        }

        return $data;
    }

    public function getTitle(): string
    {
        return __('admin.features.pages.edit');
    }
}
