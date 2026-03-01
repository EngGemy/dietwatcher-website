<?php

declare(strict_types=1);

namespace App\Filament\Resources\WhyChooseSectionResource\Pages;

use App\Filament\Resources\WhyChooseSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWhyChooseSection extends EditRecord
{
    protected static string $resource = WhyChooseSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
