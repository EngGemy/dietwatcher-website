<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialSectionHeaderResource\Pages;
use App\Models\TestimonialSectionHeader;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;

class TestimonialSectionHeaderResource extends Resource
{
    protected static ?string $model = TestimonialSectionHeader::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 65;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.testimonials');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.testimonial_section_headers.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.testimonial_section_headers.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.testimonial_section_headers.plural_model_label');
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
                                    ->label(__('admin.testimonial_section_headers.fields.badge_title') . ' (EN)'),
                                TextInput::make('title_en')
                                    ->label(__('admin.testimonial_section_headers.fields.title') . ' (EN)'),
                                Textarea::make('subtitle_en')
                                    ->label(__('admin.testimonial_section_headers.fields.subtitle') . ' (EN)'),
                            ]),
                        Tabs\Tab::make('Arabic')
                            ->schema([
                                TextInput::make('badge_title_ar')
                                    ->label(__('admin.testimonial_section_headers.fields.badge_title') . ' (AR)'),
                                TextInput::make('title_ar')
                                    ->label(__('admin.testimonial_section_headers.fields.title') . ' (AR)'),
                                Textarea::make('subtitle_ar')
                                    ->label(__('admin.testimonial_section_headers.fields.subtitle') . ' (AR)'),
                            ]),
                    ])
                    ->columnSpanFull(),

                Toggle::make('is_active')
                    ->label(__('admin.testimonial_section_headers.fields.is_active'))
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_en')
                    ->label(__('admin.testimonial_section_headers.fields.title') . ' (EN)')
                    ->searchable(),

                TextColumn::make('title_ar')
                    ->label(__('admin.testimonial_section_headers.fields.title') . ' (AR)')
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label(__('admin.testimonial_section_headers.fields.is_active'))
                    ->boolean(),

                TextColumn::make('updated_at')
                    ->label(__('admin.common.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label(__('admin.testimonial_section_headers.fields.is_active'))
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
            ->emptyStateHeading(__('admin.testimonial_section_headers.empty_state.heading'))
            ->emptyStateDescription(__('admin.testimonial_section_headers.empty_state.description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonialSectionHeaders::route('/'),
            'create' => Pages\CreateTestimonialSectionHeader::route('/create'),
            'edit' => Pages\EditTestimonialSectionHeader::route('/{record}/edit'),
        ];
    }
}
