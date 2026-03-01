<?php

declare(strict_types=1);

namespace App\Filament\Resources\FeatureResource\Pages;

use App\Filament\Resources\FeatureResource;
use App\Models\Content\FeatureTranslation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateFeature extends CreateRecord
{
    protected static string $resource = FeatureResource::class;

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
            FeatureTranslation::query()->updateOrCreate(
                [
                    'feature_id' => $record->getKey(),
                    'locale' => $locale,
                ],
                [
                    'title' => $attrs['title'] ?? '',
                    'description' => $attrs['description'] ?? null,
                ]
            );
        }
    }

    public function getTitle(): string
    {
        return __('admin.features.pages.create');
    }
}
