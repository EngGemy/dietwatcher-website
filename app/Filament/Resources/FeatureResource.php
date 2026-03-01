<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FeatureResource\Pages;
use App\Models\Content\Feature;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FeatureResource extends Resource
{
    protected static ?string $model = Feature::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-star';

    // ✅ Expert sidebar grouping
    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.content';

    // ✅ Order inside the group
    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('admin.features.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.features.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.features.plural_model_label');
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
                    TextInput::make("translations.{$code}.title")
                        ->label(__('admin.features.fields.title'))
                        ->placeholder(__('admin.features.fields.title_placeholder'))
                        ->required(fn () => $code === config('app.fallback_locale', 'en')),

                    Textarea::make("translations.{$code}.description")
                        ->label(__('admin.features.fields.description'))
                        ->placeholder(__('admin.features.fields.description_placeholder'))
                        ->rows(3),
                ]);
        }

        return $schema->components([
            Tabs::make('translations')
                ->tabs($tabs)
                ->columnSpanFull(),

            FileUpload::make('image')
                ->label(__('admin.features.fields.image'))
                ->image()
                ->disk('public')
                ->directory('features')
                ->nullable(),

            TextInput::make('icon')
                ->label(__('admin.features.fields.icon'))
                ->maxLength(64)
                ->nullable(),

            TextInput::make('order')
                ->label(__('admin.features.fields.order'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.features.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label(__('admin.features.fields.order'))
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('admin.features.fields.title'))
                    ->getStateUsing(fn (Feature $record) => $record->translate(app()->getLocale())?->title
                        ?? $record->translate('en')?->title
                        ?? '—')
                    ->limit(40),

                IconColumn::make('is_active')
                    ->label(__('admin.features.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->emptyStateHeading(__('admin.features.empty_state.heading'))
            ->emptyStateDescription(__('admin.features.empty_state.description'))
            ->filters([])

            // ✅ Filament v4 row actions
            ->recordActions([
                EditAction::make(),
            ])

            // ✅ Filament v4 bulk actions
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
            'index' => Pages\ListFeatures::route('/'),
            'create' => Pages\CreateFeature::route('/create'),
            'edit' => Pages\EditFeature::route('/{record}/edit'),
        ];
    }
}
