<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.users_permissions';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.users_permissions');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.roles.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.roles.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.roles.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('admin.roles.fields.name'))
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('guard_name')
                ->label(__('admin.roles.fields.guard_name'))
                ->default('web')
                ->required()
                ->maxLength(255),

            Select::make('permissions')
                ->label(__('admin.roles.fields.permissions'))
                ->relationship('permissions', 'name')
                ->multiple()
                ->preload()
                ->searchable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.roles.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('guard_name')
                    ->label(__('admin.roles.fields.guard_name'))
                    ->sortable(),

                TextColumn::make('permissions_count')
                    ->label(__('admin.permissions.plural_model_label'))
                    ->counts('permissions')
                    ->sortable(),
            ])
            ->emptyStateHeading(__('admin.roles.empty_state.heading'))
            ->emptyStateDescription(__('admin.roles.empty_state.description'))
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
