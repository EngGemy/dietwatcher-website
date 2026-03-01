<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.testimonials';

    protected static ?int $navigationSort = 60;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.testimonials');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.testimonials.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.testimonials.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.testimonials.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);
        $defaultLocale = config('app.fallback_locale', 'en');

        $tabs = [];
        foreach ($locales as $code => $label) {
            $tabs[] = Tabs\Tab::make($label)
                ->schema([
                    TextInput::make("translations.{$code}.author_name")
                        ->label(__('admin.testimonials.fields.author_name') . " ($code)")
                        ->placeholder(__('admin.testimonials.fields.author_name_placeholder'))
                        ->required($code === $defaultLocale),
                    TextInput::make("translations.{$code}.author_title")
                        ->label(__('admin.testimonials.fields.author_title') . " ($code)")
                        ->placeholder(__('admin.testimonials.fields.author_title_placeholder')),
                    Textarea::make("translations.{$code}.content")
                        ->label(__('admin.testimonials.fields.content') . " ($code)")
                        ->placeholder(__('admin.testimonials.fields.content_placeholder'))
                        ->required($code === $defaultLocale)
                        ->rows(4),
                ]);
        }

        return $schema->components([
            Tabs::make('Translations')
                ->tabs($tabs)
                ->columnSpanFull(),

            FileUpload::make('author_image')
                ->label(__('admin.testimonials.fields.author_image'))
                ->image()
                ->disk('public')
                ->directory('testimonials')
                ->avatar(),

            Select::make('rating')
                ->label(__('admin.testimonials.fields.rating'))
                ->options([
                    1 => __('admin.testimonials.fields.rating_1'),
                    2 => __('admin.testimonials.fields.rating_2'),
                    3 => __('admin.testimonials.fields.rating_3'),
                    4 => __('admin.testimonials.fields.rating_4'),
                    5 => __('admin.testimonials.fields.rating_5'),
                ])
                ->default(5)
                ->required(),

            TextInput::make('order_column')
                ->label(__('admin.testimonials.fields.order_column'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.testimonials.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_column')
                    ->label(__('admin.testimonials.fields.order_column'))
                    ->sortable(),

                ImageColumn::make('author_image')
                    ->label(__('admin.testimonials.fields.author_image'))
                    ->circular(),

                TextColumn::make('author_name')
                    ->label(__('admin.testimonials.fields.author_name'))
                    ->getStateUsing(fn(Testimonial $record) => $record->author_name) // Astrotomic accessor
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereTranslationLike('author_name', "%{$search}%");
                    }),

                TextColumn::make('rating')
                    ->label(__('admin.testimonials.fields.rating'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin.testimonials.fields.is_active'))
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->filters([
                Filter::make('is_active')
                    ->label(__('admin.testimonials.fields.is_active'))
                    ->query(fn($q) => $q->where('is_active', true)),
                SelectFilter::make('rating')
                    ->label(__('admin.testimonials.fields.rating'))
                    ->options([
                        1 => __('admin.testimonials.fields.rating_1'),
                        2 => __('admin.testimonials.fields.rating_2'),
                        3 => __('admin.testimonials.fields.rating_3'),
                        4 => __('admin.testimonials.fields.rating_4'),
                        5 => __('admin.testimonials.fields.rating_5'),
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
            ])
            ->emptyStateHeading(__('admin.testimonials.empty_state.heading'))
            ->emptyStateDescription(__('admin.testimonials.empty_state.description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
