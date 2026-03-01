<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.users_permissions';

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.users_permissions');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.users.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.users.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.users.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label(__('admin.users.fields.name'))
                ->placeholder(__('admin.users.fields.name_placeholder'))
                ->required()
                ->maxLength(255),

            TextInput::make('email')
                ->label(__('admin.users.fields.email'))
                ->placeholder(__('admin.users.fields.email_placeholder'))
                ->email()
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),

            TextInput::make('password')
                ->label(__('filament-panels::pages/auth/edit-profile.form.password'))
                ->password()
                ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn ($state) => filled($state))
                ->required(fn (string $context): bool => $context === 'create')
                ->maxLength(255),

            Select::make('roles')
                ->label(__('admin.users.fields.roles'))
                ->relationship('roles', 'name')
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
                    ->label(__('admin.users.fields.name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('roles.name')
                    ->label(__('admin.roles.model_label'))
                    ->badge()
                    ->separator(', '),
            ])
            ->emptyStateHeading(__('admin.users.empty_state.heading'))
            ->emptyStateDescription(__('admin.users.empty_state.description'))
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
