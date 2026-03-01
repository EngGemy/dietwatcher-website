<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanCategoryResource\Pages;

use App\Filament\Resources\PlanCategoryResource;
use App\Models\PlanCategoryTranslation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreatePlanCategory extends CreateRecord
{
    protected static string $resource = PlanCategoryResource::class;

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
            PlanCategoryTranslation::query()->updateOrCreate(
                [
                    'plan_category_id' => $record->getKey(),
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
        return __('admin.plan_categories.pages.create');
    }
}
