<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Arr;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->rolesToSync = Arr::pull($data, 'roles', []);
        return $data;
    }

    /** @var array<int, string> */
    protected array $rolesToSync = [];

    protected function afterCreate(): void
    {
        if (! empty($this->rolesToSync)) {
            $this->getRecord()->syncRoles($this->rolesToSync);
        }
    }

    public function getTitle(): string
    {
        return __('admin.users.pages.create');
    }
}
