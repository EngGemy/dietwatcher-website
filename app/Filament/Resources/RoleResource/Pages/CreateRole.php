<?php

declare(strict_types=1);

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissionsToSync = Arr::pull($data, 'permissions', []);
        return $data;
    }

    /** @var array<int, string> */
    protected array $permissionsToSync = [];

    protected function afterCreate(): void
    {
        if (! empty($this->permissionsToSync)) {
            $this->getRecord()->syncPermissions($this->permissionsToSync);
        }
    }
}
