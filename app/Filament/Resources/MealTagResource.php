<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\MealTagResource\Pages;
use App\Models\MealTag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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

class MealTagResource extends Resource
{
    protected static ?string $model = MealTag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-hashtag';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.meal_management';

    protected static ?int $navigationSort = 30;

    public static function getNavigationLabel(): string
    {
        return __('admin.meal_tags.navigation_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.meal_management');
    }

    public static function getModelLabel(): string
    {
        return __('admin.meal_tags.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.meal_tags.plural_model_label');
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

            Toggle::make('is_active')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->getStateUsing(fn(MealTag $record) => $record->name),
                IconColumn::make('is_active')->boolean(),
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
            'index' => Pages\ListMealTags::route('/'),
            'create' => Pages\CreateMealTag::route('/create'),
            'edit' => Pages\EditMealTag::route('/{record}/edit'),
        ];
    }
}
