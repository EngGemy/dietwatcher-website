<?php

namespace App\Filament\Resources\HowItWorksStepResource\Pages;

use App\Filament\Resources\HowItWorksStepResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageHowItWorksSteps extends ManageRecords
{
    protected static string $resource = HowItWorksStepResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
