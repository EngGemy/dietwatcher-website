<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqResource\Pages;

use App\Filament\Resources\FaqResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaq extends EditRecord
{
    protected static string $resource = FaqResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);

        $data['translations'] = [];
        foreach (array_keys($locales) as $locale) {
            $translation = $record->translate($locale);
            if ($translation) {
                $data['translations'][$locale] = [
                    'question' => $translation->question,
                    'answer' => $translation->answer,
                ];
            }
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['translations'])) {
            foreach ($data['translations'] as $locale => $fields) {
                $data[$locale] = $fields;
            }
            unset($data['translations']);
        }
        return $data;
    }
}
