<?php

declare(strict_types=1);

namespace App\Filament\Resources\AppDownloadSectionResource\Pages;

use App\Filament\Resources\AppDownloadSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAppDownloadSection extends EditRecord
{
    protected static string $resource = AppDownloadSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
