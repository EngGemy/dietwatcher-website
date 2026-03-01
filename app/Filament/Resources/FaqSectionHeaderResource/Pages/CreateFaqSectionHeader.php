<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqSectionHeaderResource\Pages;

use App\Filament\Resources\FaqSectionHeaderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFaqSectionHeader extends CreateRecord
{
    protected static string $resource = FaqSectionHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
