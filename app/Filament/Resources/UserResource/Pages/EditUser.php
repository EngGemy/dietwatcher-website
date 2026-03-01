<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['roles'])) {
            $this->getRecord()->syncRoles($data['roles']);
        }
        unset($data['roles']);
        return $data;
    }

    public function getTitle(): string
    {
        return __('admin.users.pages.edit');
    }
}
