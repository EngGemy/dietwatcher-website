<?php

declare(strict_types=1);

namespace App\Filament\Resources\BlogPostResource\Pages;

use App\Filament\Resources\BlogPostResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlogPost extends EditRecord
{
    protected static string $resource = BlogPostResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();

        // Load translations for EN and AR
        $locales = ['en', 'ar'];
        
        foreach ($locales as $locale) {
            $translation = $record->translate($locale);
            if ($translation) {
                $data[$locale] = [
                    'title' => $translation->title,
                    'slug' => $translation->slug,
                    'excerpt' => $translation->excerpt,
                    'content' => $translation->content,
                    'meta_title' => $translation->meta_title,
                    'meta_description' => $translation->meta_description,
                    'meta_keywords' => $translation->meta_keywords,
                    'og_title' => $translation->og_title,
                    'og_description' => $translation->og_description,
                    'og_image_path' => $translation->og_image_path,
                ];
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Data is already in the correct format for Astrotomic
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
