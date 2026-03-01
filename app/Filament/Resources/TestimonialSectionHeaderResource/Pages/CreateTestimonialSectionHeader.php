<?php

declare(strict_types=1);

namespace App\Filament\Resources\TestimonialSectionHeaderResource\Pages;

use App\Filament\Resources\TestimonialSectionHeaderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTestimonialSectionHeader extends CreateRecord
{
    protected static string $resource = TestimonialSectionHeaderResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
