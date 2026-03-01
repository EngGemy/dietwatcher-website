<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MealResource\Pages;
use App\Filament\Resources\MealResource\RelationManagers;
use App\Models\Meal;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class MealResource extends Resource
{
    protected static ?string $model = Meal::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cake';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.meal_management';

    protected static ?int $navigationSort = 10;

    public static function getNavigationLabel(): string
    {
        return __('admin.meals.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.meals.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.meals.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.meal_management');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);
        $defaultLocale = config('app.fallback_locale', 'en');

        $translatableTabs = [];
        foreach ($locales as $code => $label) {
            $translatableTabs[] = Tab::make($label)
                ->schema([
                    TextInput::make("name.{$code}")
                        ->label("Name ($code)")
                        ->required($code === $defaultLocale),
                    Textarea::make("description.{$code}")
                        ->label("Description ($code)")
                        ->rows(3),
                ]);
        }

        return $schema->components([
            Tabs::make(__('admin.meals.sections.content'))
                ->tabs($translatableTabs)
                ->columnSpanFull(),

            Section::make(__('admin.meals.sections.details'))->schema([
                Select::make('meal_group_id')
                    ->relationship('group', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name) // Spatie accessor
                    ->searchable()
                    ->preload(),

                TextInput::make('price')
                    ->numeric()
                    ->prefix('SAR')
                    ->required(),

                TextInput::make('calories')
                    ->numeric(),

                Section::make(__('admin.meals.sections.macros'))->schema([
                    TextInput::make('protein')->numeric()->suffix('g'),
                    TextInput::make('carbs')->numeric()->suffix('g'),
                    TextInput::make('fat')->numeric()->suffix('g'),
                ])->columns(3),
            ])->columns(2),

            Section::make(__('admin.meals.sections.organization'))->schema([
                Select::make('categories')
                    ->relationship('categories', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                    ->multiple()
                    ->preload(),

                Select::make('tags')
                    ->relationship('tags', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                    ->multiple()
                    ->preload(),

                Select::make('groups')
                    ->relationship('groups', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                    ->multiple()
                    ->preload(),
            ])->columns(2),

            Section::make(__('admin.meals.sections.media_status'))->schema([
                FileUpload::make('image')
                    ->label('Main Image')
                    ->image()
                    ->disk('public')
                    ->directory('meals'),

                Toggle::make('is_active')->default(true),
                Toggle::make('is_store_product')->default(false),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),

                TextColumn::make('name')
                    ->getStateUsing(fn(Meal $record) => $record->name) // Spatie accessor auto-detects locale
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereRaw('LOWER(JSON_EXTRACT(name, "$.en")) LIKE ?', ["%{$search}%"])
                            ->orWhereRaw('LOWER(JSON_EXTRACT(name, "$.ar")) LIKE ?', ["%{$search}%"]);
                    }),

                TextColumn::make('price')->money('SAR')->sortable(),
                TextColumn::make('calories')->sortable(),

                IconColumn::make('is_store_product')->boolean(),
                IconColumn::make('is_active')->boolean(),

                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Filter::make('is_active')->query(fn($q) => $q->where('is_active', true)),
                Filter::make('is_store_product')->query(fn($q) => $q->where('is_store_product', true)),

                SelectFilter::make('group')
                    ->relationship('group', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name),

                SelectFilter::make('category')
                    ->relationship('categories', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name),

                SelectFilter::make('tag')
                    ->relationship('tags', 'name')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name),

                Filter::make('price_range')
                    ->form([
                        TextInput::make('price_from')->numeric(),
                        TextInput::make('price_to')->numeric(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['price_from'], fn($q, $v) => $q->where('price', '>=', $v))
                            ->when($data['price_to'], fn($q, $v) => $q->where('price', '<=', $v));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
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
            RelationManagers\MealImagesRelationManager::class,
            RelationManagers\IngredientsRelationManager::class,
            RelationManagers\OffersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeals::route('/'),
            'create' => Pages\CreateMeal::route('/create'),
            'edit' => Pages\EditMeal::route('/{record}/edit'),
        ];
    }
}
