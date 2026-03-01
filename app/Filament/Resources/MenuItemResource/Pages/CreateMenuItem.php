<?php

declare(strict_types=1);

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Filament\Resources\MenuItemResource;
use App\Models\Content\MenuItem;
use App\Models\Content\MenuItemTranslation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateMenuItem extends CreateRecord
{
    protected static string $resource = MenuItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $translations = Arr::pull($data, 'translations', []);
        $this->translationsData = $translations;

        return $data;
    }

    /** @var array<string, array<string, mixed>> */
    protected array $translationsData = [];

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        foreach ($this->translationsData as $locale => $attrs) {
            if (empty($attrs['label'] ?? null)) {
                continue;
            }
            MenuItemTranslation::query()->updateOrCreate(
                [
                    'menu_item_id' => $record->getKey(),
                    'locale' => $locale,
                ],
                ['label' => $attrs['label']]
            );
        }
    }

    public function getTitle(): string
    {
        return __('admin.menu_items.pages.create');
    }
}
