<?php

declare(strict_types=1);

namespace App\Filament\Resources\WhyChooseSectionResource\Pages;

use App\Filament\Resources\WhyChooseSectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhyChooseSection extends CreateRecord
{
    protected static string $resource = WhyChooseSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
