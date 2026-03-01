<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MenuItemResource\Pages;
use App\Models\Content\MenuItem;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MenuItemResource extends Resource
{
    protected static ?string $model = MenuItem::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bars-3';

    public static function getNavigationLabel(): string
    {
        return __('admin.menu_items.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.menu_items.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.menu_items.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.content');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);

        $tabs = [];
        foreach ($locales as $code => $name) {
            $tabs[] = Tab::make($name)
                ->schema([
                    TextInput::make("translations.{$code}.label")
                        ->label(__('admin.menu_items.fields.label'))
                        ->placeholder(__('admin.menu_items.fields.label_placeholder'))
                        ->required(fn () => $code === config('app.fallback_locale', 'en')),
                ]);
        }

        return $schema->components([
            Tabs::make('translations')
                ->tabs($tabs)
                ->columnSpanFull(),

            TextInput::make('url')
                ->label(__('admin.menu_items.fields.url'))
                ->placeholder(__('admin.menu_items.fields.url_placeholder'))
                ->url()
                ->maxLength(500),

            TextInput::make('route_name')
                ->label(__('admin.menu_items.fields.route_name'))
                ->placeholder(__('admin.menu_items.fields.route_name_placeholder')),

            Select::make('target')
                ->label(__('admin.menu_items.fields.target'))
                ->options([
                    '_self' => __('admin.menu_items.fields.target_self'),
                    '_blank' => __('admin.menu_items.fields.target_blank'),
                ])
                ->default('_self'),

            Select::make('parent_id')
                ->label(__('admin.menu_items.fields.parent'))
                ->relationship(
                    name: 'parent',
                    titleAttribute: 'id',
                    modifyQueryUsing: fn (Builder $q) => $q->orderBy('order'),
                    ignoreRecord: true
                )
                ->getOptionLabelFromRecordUsing(
                    fn (MenuItem $record) =>
                        $record->translate(app()->getLocale())?->label
                        ?? $record->translate('en')?->label
                        ?? (string) $record->id
                )
                ->searchable(false)
                ->nullable(),

            TextInput::make('order')
                ->label(__('admin.menu_items.fields.order'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.menu_items.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label(__('admin.menu_items.fields.order'))
                    ->sortable(),

                TextColumn::make('label')
                    ->label(__('admin.menu_items.fields.label'))
                    ->getStateUsing(fn (MenuItem $record) => $record->translate(app()->getLocale())?->label
                        ?? $record->translate('en')?->label
                        ?? '—')
                    ->searchable(),

                TextColumn::make('url')
                    ->label(__('admin.menu_items.fields.url'))
                    ->limit(40),

                IconColumn::make('is_active')
                    ->label(__('admin.menu_items.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->emptyStateHeading(__('admin.menu_items.empty_state.heading'))
            ->emptyStateDescription(__('admin.menu_items.empty_state.description'))
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
            'index' => Pages\ListMenuItems::route('/'),
            'create' => Pages\CreateMenuItem::route('/create'),
            'edit' => Pages\EditMenuItem::route('/{record}/edit'),
        ];
    }
}
