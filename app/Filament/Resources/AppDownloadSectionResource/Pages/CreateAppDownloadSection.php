<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppDownloadSectionResource\Pages;

use App\Filament\Resources\AppDownloadSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAppDownloadSection extends CreateRecord
{
    protected static string $resource = AppDownloadSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
