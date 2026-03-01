<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqSectionHeaderResource\Pages;

use App\Filament\Resources\FaqSectionHeaderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditFaqSectionHeader extends EditRecord
{
    protected static string $resource = FaqSectionHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
