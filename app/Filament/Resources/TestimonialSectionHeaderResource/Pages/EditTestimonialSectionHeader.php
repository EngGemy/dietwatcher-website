<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestimonialSectionHeaderResource\Pages;

use App\Filament\Resources\TestimonialSectionHeaderResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTestimonialSectionHeader extends EditRecord
{
    protected static string $resource = TestimonialSectionHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
