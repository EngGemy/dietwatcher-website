<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestimonialSectionHeaderResource\Pages;

use App\Filament\Resources\TestimonialSectionHeaderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTestimonialSectionHeaders extends ListRecords
{
    protected static string $resource = TestimonialSectionHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('admin.testimonial_section_headers.pages.create')),
        ];
    }
}
