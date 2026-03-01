<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\HeroSectionResource\Pages;
use App\Models\Content\HeroSection;
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

class HeroSectionResource extends Resource
{
    protected static ?string $model = HeroSection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.content';

    public static function getNavigationLabel(): string
    {
        return __('admin.hero_sections.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.hero_sections.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.hero_sections.plural_model_label');
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
                        ->label(__('admin.hero_sections.fields.title'))
                        ->placeholder(__('admin.hero_sections.fields.title_placeholder'))
                        ->required(fn () => $code === config('app.fallback_locale', 'en')),

                    Textarea::make("translations.{$code}.subtitle")
                        ->label(__('admin.hero_sections.fields.subtitle'))
                        ->placeholder(__('admin.hero_sections.fields.subtitle_placeholder'))
                        ->rows(3),

                    TextInput::make("translations.{$code}.cta_text")
                        ->label(__('admin.hero_sections.fields.cta_text'))
                        ->placeholder(__('admin.hero_sections.fields.cta_text_placeholder')),

                    TextInput::make("translations.{$code}.cta_secondary_text")
                        ->label(__('admin.hero_sections.fields.cta_secondary_text'))
                        ->placeholder(__('admin.hero_sections.fields.cta_secondary_text_placeholder')),
                ]);
        }

        return $schema->components([
            Tabs::make('translations')
                ->tabs($tabs)
                ->columnSpanFull(),

            FileUpload::make('image_desktop')
                ->label(__('admin.hero_sections.fields.image_desktop'))
                ->image()
                ->disk('public')
                ->directory('hero')
                ->nullable(),

            FileUpload::make('image_mobile')
                ->label(__('admin.hero_sections.fields.image_mobile'))
                ->image()
                ->disk('public')
                ->directory('hero')
                ->nullable(),

            TextInput::make('app_store_url')
                ->label(__('admin.hero_sections.fields.app_store_url'))
                ->url()
                ->maxLength(500),

            TextInput::make('play_store_url')
                ->label(__('admin.hero_sections.fields.play_store_url'))
                ->url()
                ->maxLength(500),

            TextInput::make('order')
                ->label(__('admin.hero_sections.fields.order'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.hero_sections.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order')
                    ->label(__('admin.hero_sections.fields.order'))
                    ->sortable(),

                TextColumn::make('title')
                    ->label(__('admin.hero_sections.fields.title'))
                    ->getStateUsing(fn (HeroSection $record) => $record->translate(app()->getLocale())?->title
                        ?? $record->translate('en')?->title
                        ?? '—')
                    ->limit(50),

                IconColumn::make('is_active')
                    ->label(__('admin.hero_sections.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('order')
            ->reorderable('order')
            ->emptyStateHeading(__('admin.hero_sections.empty_state.heading'))
            ->emptyStateDescription(__('admin.hero_sections.empty_state.description'))
            ->filters([])

            // ✅ Filament v4 record actions (row actions)
            ->recordActions([
                EditAction::make(),
            ])

            // ✅ Filament v4 toolbar actions (bulk actions live here)
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
            'index' => Pages\ListHeroSections::route('/'),
            'create' => Pages\CreateHeroSection::route('/create'),
            'edit' => Pages\EditHeroSection::route('/{record}/edit'),
        ];
    }
}
