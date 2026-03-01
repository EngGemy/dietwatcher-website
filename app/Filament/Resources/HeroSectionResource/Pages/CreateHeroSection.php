<?php

declare(strict_types=1);

namespace App\Filament\Resources\HeroSectionResource\Pages;

use App\Filament\Resources\HeroSectionResource;
use App\Models\Content\HeroSectionTranslation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateHeroSection extends CreateRecord
{
    protected static string $resource = HeroSectionResource::class;

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
            if (empty($attrs['title'] ?? null)) {
                continue;
            }
            HeroSectionTranslation::query()->updateOrCreate(
                [
                    'hero_section_id' => $record->getKey(),
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
    }

    public function getTitle(): string
    {
        return __('admin.hero_sections.pages.create');
    }
}
