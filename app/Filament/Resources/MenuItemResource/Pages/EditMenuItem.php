<?php

declare(strict_types=1);

namespace App\Filament\Resources\MenuItemResource\Pages;

use App\Filament\Resources\MenuItemResource;
use App\Models\Content\MenuItem;
use App\Models\Content\MenuItemTranslation;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditMenuItem extends EditRecord
{
    protected static string $resource = MenuItemResource::class;

    public function mutateFormDataBeforeFill(array $data): array
    {
        /** @var MenuItem $record */
        $record = $this->getRecord();
        $locales = array_keys(config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']));
        $data['translations'] = [];
        foreach ($locales as $locale) {
            $trans = $record->translate($locale);
            $data['translations'][$locale] = [
                'label' => $trans?->label ?? '',
            ];
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $translations = Arr::pull($data, 'translations', []);

        foreach ($translations as $locale => $attrs) {
            if (($attrs['label'] ?? '') === '') {
                continue;
            }
            MenuItemTranslation::query()->updateOrCreate(
                [
                    'menu_item_id' => $this->getRecord()->getKey(),
                    'locale' => $locale,
                ],
                ['label' => $attrs['label']]
            );
        }

        return $data;
    }

    public function getTitle(): string
    {
        return __('admin.menu_items.pages.edit');
    }
}
