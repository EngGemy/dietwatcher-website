<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\PlanCategoryResource\Pages;
use App\Models\PlanCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanCategoryResource extends Resource
{
    protected static ?string $model = PlanCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.meal_plans';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.meal_plans');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.plan_categories.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.plan_categories.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.plan_categories.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);

        $tabs = [];
        foreach ($locales as $code => $name) {
            $tabs[] = Tab::make($name)
                ->schema([
                    TextInput::make("translations.{$code}.name")
                        ->label(__('admin.plan_categories.fields.name'))
                        ->placeholder(__('admin.plan_categories.fields.name_placeholder'))
                        ->required(fn() => $code === config('app.fallback_locale', 'en')),
                ]);
        }

        return $schema->components([
            Tabs::make('translations')
                ->tabs($tabs)
                ->columnSpanFull(),

            TextInput::make('slug')
                ->label(__('admin.plan_categories.fields.slug'))
                ->helperText(__('admin.plan_categories.fields.slug_help'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            TextInput::make('order_column')
                ->label(__('admin.plan_categories.fields.order_column'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.plan_categories.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_column')
                    ->label(__('admin.plan_categories.fields.order_column'))
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('admin.plan_categories.fields.name'))
                    ->getStateUsing(fn(PlanCategory $record) => $record->translate(app()->getLocale())?->name
                        ?? $record->translate('en')?->name
                        ?? '—')
                    ->limit(40)
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereTranslationLike('name', "%{$search}%");
                    }),

                TextColumn::make('slug')
                    ->label(__('admin.plan_categories.fields.slug'))
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label(__('admin.plan_categories.fields.is_active'))
                    ->boolean(),

                TextColumn::make('plans_count')
                    ->label(__('admin.plans.plural_model_label'))
                    ->counts('plans'),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->emptyStateHeading(__('admin.plan_categories.empty_state.heading'))
            ->emptyStateDescription(__('admin.plan_categories.empty_state.description'))
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlanCategories::route('/'),
            'create' => Pages\CreatePlanCategory::route('/create'),
            'edit' => Pages\EditPlanCategory::route('/{record}/edit'),
        ];
    }
}
