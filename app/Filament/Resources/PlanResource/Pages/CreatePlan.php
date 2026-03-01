<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\PlanTranslation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

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
            PlanTranslation::query()->updateOrCreate(
                [
                    'plan_id' => $record->getKey(),
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
    }

    public function getTitle(): string
    {
        return __('admin.plans.pages.create');
    }
}
