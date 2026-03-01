<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.meal_management';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('admin.categories.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.categories.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.categories.plural_model_label');
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
                ]);
        }

        return $schema->components([
            Tabs::make(__('admin.common.translations'))
                ->tabs($translatableTabs)
                ->columnSpanFull(),

            TextInput::make('type'), // Or Select if enums defined
            FileUpload::make('icon')->image()->disk('public')->directory('categories'),
            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->getStateUsing(fn(Category $record) => $record->name)
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereRaw('LOWER(JSON_EXTRACT(name, "$.en")) LIKE ?', ["%{$search}%"]);
                    }),
                TextColumn::make('type')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('type')->options([
                    'meal' => 'Meal',
                    'blog' => 'Blog',
                ]),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
