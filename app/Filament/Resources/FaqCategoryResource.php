<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FaqCategoryResource\Pages;
use App\Models\FaqCategory;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class FaqCategoryResource extends Resource
{
    protected static ?string $model = FaqCategory::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.faq';

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.faq');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.faq_categories.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.faq_categories.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.faq_categories.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);
        $defaultLocale = config('app.fallback_locale', 'en');

        $tabs = [];
        foreach ($locales as $code => $label) {
            $tabs[] = Tabs\Tab::make($label)
                ->schema([
                    TextInput::make("translations.{$code}.name")
                        ->label(__('admin.faq_categories.fields.name') . " ($code)")
                        ->required($code === $defaultLocale),
                ]);
        }

        return $schema->components([
            Tabs::make(__('admin.common.translations'))
                ->tabs($tabs)
                ->columnSpanFull(),

            TextInput::make('slug')
                ->label(__('admin.faq_categories.fields.slug'))
                ->required()
                ->unique(ignoreRecord: true),

            TextInput::make('icon')
                ->label(__('admin.faq_categories.fields.icon'))
                ->placeholder('crown, wallet, truck, phone')
                ->helperText(__('admin.faq_categories.fields.icon_help'))
                ->nullable(),

            TextInput::make('order_column')
                ->label(__('admin.faq_categories.fields.order_column'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.faq_categories.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_column')
                    ->label(__('admin.faq_categories.fields.order_column'))
                    ->sortable(),

                TextColumn::make('name')
                    ->label(__('admin.faq_categories.fields.name'))
                    ->getStateUsing(fn(FaqCategory $record) => $record->name)
                    ->searchable(),

                TextColumn::make('slug')
                    ->label(__('admin.faq_categories.fields.slug'))
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label(__('admin.faq_categories.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->filters([
                Filter::make('is_active')
                    ->label(__('admin.faq_categories.fields.is_active'))
                    ->query(fn($q) => $q->where('is_active', true)),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.faq_categories.empty_state.heading'))
            ->emptyStateDescription(__('admin.faq_categories.empty_state.description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqCategories::route('/'),
            'create' => Pages\CreateFaqCategory::route('/create'),
            'edit' => Pages\EditFaqCategory::route('/{record}/edit'),
        ];
    }
}
