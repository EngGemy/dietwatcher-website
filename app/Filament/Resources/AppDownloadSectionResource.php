<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\AppDownloadSectionResource\Pages;
use App\Models\AppDownloadSection;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class AppDownloadSectionResource extends Resource
{
    protected static ?string $model = AppDownloadSection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static ?int $navigationSort = 45;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.content');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.app_download_sections.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.app_download_sections.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.app_download_sections.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.common.translations'))
                    ->tabs([
                        Tabs\Tab::make('English')
                            ->schema([
                                TextInput::make('badge_title_en')
                                    ->label(__('admin.app_download_sections.fields.badge_title') . ' (EN)'),
                                TextInput::make('title_en')
                                    ->label(__('admin.app_download_sections.fields.title') . ' (EN)'),
                                Textarea::make('subtitle_en')
                                    ->label(__('admin.app_download_sections.fields.subtitle') . ' (EN)'),
                            ]),
                        Tabs\Tab::make('Arabic')
                            ->schema([
                                TextInput::make('badge_title_ar')
                                    ->label(__('admin.app_download_sections.fields.badge_title') . ' (AR)'),
                                TextInput::make('title_ar')
                                    ->label(__('admin.app_download_sections.fields.title') . ' (AR)'),
                                Textarea::make('subtitle_ar')
                                    ->label(__('admin.app_download_sections.fields.subtitle') . ' (AR)'),
                            ]),
                    ])
                    ->columnSpanFull(),

                FileUpload::make('mobile_image')
                    ->label(__('admin.app_download_sections.fields.mobile_image'))
                    ->image()
                    ->disk('public')
                    ->directory('app-download')
                    ->helperText(__('admin.app_download_sections.fields.mobile_image_help'))
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label(__('admin.app_download_sections.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('mobile_image')
                    ->label(__('admin.app_download_sections.fields.mobile_image'))
                    ->defaultImageUrl(asset('assets/images/app-screens.png')),

                TextColumn::make('title_en')
                    ->label(__('admin.app_download_sections.fields.title') . ' (EN)')
                    ->searchable(),

                TextColumn::make('title_ar')
                    ->label(__('admin.app_download_sections.fields.title') . ' (AR)')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label(__('admin.app_download_sections.fields.is_active'))
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label(__('admin.common.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label(__('admin.app_download_sections.fields.is_active'))
                    ->query(fn($query) => $query->where('is_active', true)),
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
            ->emptyStateHeading(__('admin.app_download_sections.empty_state.heading'))
            ->emptyStateDescription(__('admin.app_download_sections.empty_state.description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppDownloadSections::route('/'),
            'create' => Pages\CreateAppDownloadSection::route('/create'),
            'edit' => Pages\EditAppDownloadSection::route('/{record}/edit'),
        ];
    }
}
