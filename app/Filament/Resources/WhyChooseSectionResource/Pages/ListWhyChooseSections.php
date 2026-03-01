<?php

declare(strict_types=1);

namespace App\Filament\Resources\WhyChooseSectionResource\Pages;

use App\Filament\Resources\WhyChooseSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWhyChooseSections extends ListRecords
{
    protected static string $resource = WhyChooseSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.why_choose_sections.pages.create')),
        ];
    }
}
