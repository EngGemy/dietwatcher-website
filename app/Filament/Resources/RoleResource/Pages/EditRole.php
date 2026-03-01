<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Arr;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    public function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = $this->getRecord()->permissions->pluck('id')->toArray();
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['permissions'])) {
            $this->getRecord()->syncPermissions($data['permissions']);
        }
        unset($data['permissions']);
        return $data;
    }
}
