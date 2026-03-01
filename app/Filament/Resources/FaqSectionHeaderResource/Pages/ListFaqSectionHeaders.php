<?php

declare(strict_types=1);

namespace App\Filament\Resources\FaqSectionHeaderResource\Pages;

use App\Filament\Resources\FaqSectionHeaderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListFaqSectionHeaders extends ListRecords
{
    protected static string $resource = FaqSectionHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.faq_section_headers.pages.create')),
        ];
    }
}
