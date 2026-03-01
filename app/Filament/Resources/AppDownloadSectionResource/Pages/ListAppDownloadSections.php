<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppDownloadSectionResource\Pages;

use App\Filament\Resources\AppDownloadSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAppDownloadSections extends ListRecords
{
    protected static string $resource = AppDownloadSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.app_download_sections.pages.create')),
        ];
    }
}
