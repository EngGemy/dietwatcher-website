<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Models\Plan;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.meal_plans';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.plans.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.plans.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.plans.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.meal_plans');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);

        $tabs = [];
        foreach ($locales as $code => $name) {
            $tabs[] = Tab::make($name)
                ->schema([
                    TextInput::make("translations.{$code}.name")
                        ->label(__('admin.plans.fields.name'))
                        ->placeholder(__('admin.plans.fields.name_placeholder'))
                        ->required(fn() => $code === config('app.fallback_locale', 'en'))
                        ->maxLength(255),

                    TextInput::make("translations.{$code}.subtitle")
                        ->label(__('admin.plans.fields.subtitle'))
                        ->placeholder(__('admin.plans.fields.subtitle_placeholder'))
                        ->maxLength(255),

                    Textarea::make("translations.{$code}.description")
                        ->label(__('admin.plans.fields.description'))
                        ->placeholder(__('admin.plans.fields.description_placeholder'))
                        ->rows(4),

                    Textarea::make("translations.{$code}.ingredients")
                        ->label(__('admin.plans.fields.ingredients'))
                        ->placeholder(__('admin.plans.fields.ingredients_placeholder'))
                        ->rows(3),

                    Textarea::make("translations.{$code}.benefits")
                        ->label(__('admin.plans.fields.benefits'))
                        ->placeholder(__('admin.plans.fields.benefits_placeholder'))
                        ->rows(3),
                ]);
        }

        return $schema->components([
            Tabs::make('translations')
                ->tabs($tabs)
                ->columnSpanFull(),

            FileUpload::make('hero_image')
                ->label(__('admin.plans.fields.hero_image'))
                ->image()
                ->disk('public')
                ->directory('plans/hero')
                ->nullable(),

            TextInput::make('order_column')
                ->label(__('admin.plans.fields.order_column'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.plans.fields.is_active'))
                ->default(true),

            Toggle::make('show_in_app')
                ->label(__('admin.plans.fields.show_in_app'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_column')
                    ->label(__('admin.plans.fields.order_column'))
                    ->sortable(),

                ImageColumn::make('hero_image')
                    ->label(__('admin.plans.fields.hero_image'))
                    ->circular()
                    ->defaultImageUrl(url('/images/placeholder.png')),

                TextColumn::make('name')
                    ->label(__('admin.plans.fields.name'))
                    ->getStateUsing(fn(Plan $record) => $record->translate(app()->getLocale())?->name
                        ?? $record->translate('en')?->name
                        ?? '—')
                    ->limit(30)
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereTranslationLike('name', "%{$search}%");
                    }),

                TextColumn::make('calories_display')
                    ->label(__('admin.plans.tabs.calories'))
                    ->getStateUsing(fn(Plan $record) => $record->card_calories_text ?? '—')
                    ->limit(20),

                TextColumn::make('duration_display')
                    ->label(__('admin.plans.tabs.durations'))
                    ->getStateUsing(fn(Plan $record) => $record->card_days_text ?? '—')
                    ->limit(15),

                TextColumn::make('price_display')
                    ->label(__('admin.plans.fields.price_display'))
                    ->getStateUsing(fn(Plan $record) => $record->card_price_text ?? '—')
                    ->limit(15),

                IconColumn::make('is_active')
                    ->label(__('admin.plans.fields.is_active'))
                    ->boolean(),

                IconColumn::make('show_in_app')
                    ->label(__('admin.plans.fields.show_in_app'))
                    ->boolean(),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->emptyStateHeading(__('admin.plans.empty_state.heading'))
            ->emptyStateDescription(__('admin.plans.empty_state.description'))
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PlanImagesRelationManager::class,
            RelationManagers\PlanCaloriesRelationManager::class,
            RelationManagers\PlanDurationsRelationManager::class,
            RelationManagers\PlanMenusRelationManager::class,
            RelationManagers\PlanCategoriesRelationManager::class,
            RelationManagers\MealTypesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
