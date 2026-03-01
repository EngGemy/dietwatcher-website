<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BlogTagResource\Pages;
use App\Models\BlogTag;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogTagResource extends Resource
{
    protected static ?string $model = BlogTag::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.blog';

    protected static ?int $navigationSort = 112;

    public static function getNavigationLabel(): string
    {
        return __('admin.blog_tags.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.blog_tags.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.blog_tags.plural_model_label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.blog');
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
                        ->label(__('admin.blog_tags.fields.name') . " ({$code})")
                        ->required($code === $defaultLocale)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) use ($code, $defaultLocale) {
                            if ($code === $defaultLocale && empty($get('slug'))) {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->maxLength(255),
                ]);
        }

        return $schema->components([
            Tabs::make(__('admin.blog_tags.fields.name'))
                ->tabs($tabs)
                ->columnSpanFull(),

            TextInput::make('slug')
                ->label(__('admin.blog_tags.fields.slug'))
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(255),

            Toggle::make('is_active')
                ->label(__('admin.blog_tags.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.blog_tags.fields.name'))
                    ->getStateUsing(fn (BlogTag $record) => $record->name)
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label(__('admin.blog_tags.fields.slug'))
                    ->searchable()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin.blog_tags.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('posts_count')
                    ->label(__('admin.blog_tags.fields.posts_count'))
                    ->counts('posts')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label(__('admin.blog_tags.fields.is_active'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('Active'))
                    ->falseLabel(__('Inactive')),
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
            'index' => Pages\ListBlogTags::route('/'),
            'create' => Pages\CreateBlogTag::route('/create'),
            'edit' => Pages\EditBlogTag::route('/{record}/edit'),
        ];
    }
}
