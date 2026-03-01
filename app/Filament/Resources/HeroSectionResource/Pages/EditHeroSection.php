<?php

declare(strict_types=1);

namespace App\Filament\Resources\HeroSectionResource\Pages;

use App\Filament\Resources\HeroSectionResource;
use App\Models\Content\HeroSection;
use App\Models\Content\HeroSectionTranslation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditHeroSection extends EditRecord
{
    protected static string $resource = HeroSectionResource::class;

    public function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $locales = array_keys(config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']));
        $data['translations'] = [];
        foreach ($locales as $locale) {
            $trans = $record->translate($locale);
            $data['translations'][$locale] = [
                'title' => $trans?->title ?? '',
                'subtitle' => $trans?->subtitle ?? '',
                'cta_text' => $trans?->cta_text ?? '',
                'cta_secondary_text' => $trans?->cta_secondary_text ?? '',
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $translations = Arr::pull($data, 'translations', []);
        foreach ($translations as $locale => $attrs) {
            HeroSectionTranslation::query()->updateOrCreate(
                [
                    'hero_section_id' => $this->getRecord()->getKey(),
                    'locale' => $locale,
                ],
                [
                    'title' => $attrs['title'] ?? '',
                    'subtitle' => $attrs['subtitle'] ?? null,
                    'cta_text' => $attrs['cta_text'] ?? null,
                    'cta_secondary_text' => $attrs['cta_secondary_text'] ?? null,
                ]
            );
        }

        return $data;
    }

    public function getTitle(): string
    {
        return __('admin.hero_sections.pages.edit');
    }
}
