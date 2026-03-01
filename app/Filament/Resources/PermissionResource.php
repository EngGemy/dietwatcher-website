<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Permission;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.users_permissions';

    protected static bool $shouldRegisterNavigation = true;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.users_permissions');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.permissions.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.permissions.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.permissions.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('admin.permissions.fields.name'))
                ->placeholder(__('admin.permissions.fields.name_placeholder'))
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('guard_name')
                ->label(__('admin.permissions.fields.guard_name'))
                ->default('web')
                ->required()
                ->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.permissions.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label(__('admin.permissions.fields.guard_name'))
                    ->sortable(),
            ])
            ->filters([])

            // ✅ Filament v4: row actions
            ->recordActions([
                EditAction::make(),
            ])

            // ✅ Filament v4: bulk actions
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
