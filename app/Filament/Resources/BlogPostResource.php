<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.blog';

    protected static ?int $navigationSort = 110;

    public static function getNavigationLabel(): string
    {
        return __('admin.blog_posts.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.blog_posts.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.blog_posts.plural_model_label');
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
                    TextInput::make("{$code}.title")
                        ->label(__('admin.blog_posts.fields.title') . " ({$code})")
                        ->required($code === $defaultLocale)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function ($state, $set, $get) use ($code, $defaultLocale) {
                            if ($code === $defaultLocale && empty($get("{$code}.slug"))) {
                                $set("{$code}.slug", Str::slug($state));
                            }
                        })
                        ->maxLength(255),

                    TextInput::make("{$code}.slug")
                        ->label(__('admin.blog_posts.fields.slug'))
                        ->required($code === $defaultLocale)
                        ->maxLength(255),

                    Textarea::make("{$code}.excerpt")
                        ->label(__('admin.blog_posts.fields.excerpt') . " ({$code})")
                        ->rows(3),

                    RichEditor::make("{$code}.content")
                        ->label(__('admin.blog_posts.fields.content') . " ({$code})"),

                    Section::make(__('admin.blog_posts.sections.seo_social') . " ({$code})")
                        ->schema([
                            TextInput::make("{$code}.meta_title")
                                ->label(__('admin.blog_posts.fields.meta_title'))
                                ->maxLength(60),

                            Textarea::make("{$code}.meta_description")
                                ->label(__('admin.blog_posts.fields.meta_description'))
                                ->rows(2)
                                ->maxLength(160),

                            TextInput::make("{$code}.meta_keywords")
                                ->label(__('admin.blog_posts.fields.meta_keywords'))
                                ->helperText(__('Comma-separated')),

                            TextInput::make("{$code}.og_title")
                                ->label(__('admin.blog_posts.fields.og_title')),

                            Textarea::make("{$code}.og_description")
                                ->label(__('admin.blog_posts.fields.og_description'))
                                ->rows(2),

                            FileUpload::make("{$code}.og_image_path")
                                ->label(__('admin.blog_posts.fields.og_image'))
                                ->image()
                                ->disk('public')
                                ->directory('blog/og')
                                ->maxSize(2048),
                        ])
                        ->collapsible()
                        ->collapsed(),
                ]);
        }

        return $schema->components([
            Tabs::make(__('admin.blog_posts.fields.content'))
                ->tabs($tabs)
                ->columnSpanFull(),

            Section::make(__('admin.blog_posts.sections.publication'))
                ->schema([
                    Select::make('status')
                        ->label(__('admin.blog_posts.fields.status'))
                        ->options([
                            'draft' => __('admin.blog_posts.fields.status_draft'),
                            'published' => __('admin.blog_posts.fields.status_published'),
                            'scheduled' => __('admin.blog_posts.fields.status_scheduled'),
                            'archived' => __('admin.blog_posts.fields.status_archived'),
                        ])
                        ->required()
                        ->default('draft'),

                    DateTimePicker::make('published_at')
                        ->label(__('admin.blog_posts.fields.published_at')),

                    DateTimePicker::make('scheduled_at')
                        ->label(__('admin.blog_posts.fields.scheduled_at')),

                    Toggle::make('is_featured')
                        ->label(__('admin.blog_posts.fields.is_featured'))
                        ->default(false),

                    Toggle::make('allow_comments')
                        ->label(__('admin.blog_posts.fields.allow_comments'))
                        ->default(true),
                ])->columns(2),

            Section::make(__('admin.blog_posts.sections.organization'))
                ->schema([
                    Select::make('blog_category_id')
                        ->label(__('admin.blog_posts.fields.category'))
                        ->relationship('category', 'slug')
                        ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                        ->searchable()
                        ->preload(),

                    Select::make('author_id')
                        ->label(__('admin.blog_posts.fields.author'))
                        ->relationship('author', 'name')
                        ->searchable()
                        ->preload(),

                    Select::make('tags')
                        ->label(__('admin.blog_posts.fields.tags'))
                        ->relationship('tags', 'slug')
                        ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                        ->multiple()
                        ->searchable()
                        ->preload(),
                ])->columns(2),

            Section::make(__('admin.blog_posts.sections.media_stats'))
                ->schema([
                    FileUpload::make('cover_image_path')
                        ->label(__('admin.blog_posts.fields.cover_image'))
                        ->image()
                        ->disk('public')
                        ->directory('blog/covers')
                        ->maxSize(4096)
                        ->imageEditor(),

                    TextInput::make('reading_time_minutes')
                        ->label(__('admin.blog_posts.fields.reading_time'))
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(120),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('views_count')
                                ->label(__('admin.blog_posts.fields.views_count'))
                                ->numeric()
                                ->default(0)
                                ->disabled(),

                            TextInput::make('likes_count')
                                ->label(__('admin.blog_posts.fields.likes_count'))
                                ->numeric()
                                ->default(0)
                                ->disabled(),
                        ]),
                ])->columns(2),

            Section::make(__('admin.blog_posts.sections.seo_settings'))
                ->schema([
                    TextInput::make('canonical_url')
                        ->label(__('admin.blog_posts.fields.canonical_url'))
                        ->url(),

                    Grid::make(2)
                        ->schema([
                            Toggle::make('seo_indexable')
                                ->label(__('admin.blog_posts.fields.seo_indexable'))
                                ->default(true),

                            Toggle::make('seo_follow')
                                ->label(__('admin.blog_posts.fields.seo_follow'))
                                ->default(true),
                        ]),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('cover_image_path')
                    ->label(__('admin.blog_posts.fields.cover_image'))
                    ->circular()
                    ->defaultImageUrl(asset('assets/images/blog-1.png')),

                TextColumn::make('title')
                    ->label(__('admin.blog_posts.fields.title'))
                    ->getStateUsing(fn(BlogPost $record) => $record->title)
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->wrap(),

                TextColumn::make('category.name')
                    ->label(__('admin.blog_posts.fields.category'))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label(__('admin.blog_posts.fields.status'))
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'scheduled' => 'warning',
                        'archived' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => __(ucfirst($state)))
                    ->sortable(),

                IconColumn::make('is_featured')
                    ->label(__('admin.blog_posts.fields.is_featured'))
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('views_count')
                    ->label(__('admin.blog_posts.fields.views_count'))
                    ->numeric()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('published_at')
                    ->label(__('admin.blog_posts.fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.blog_posts.fields.status'))
                    ->options([
                        'draft' => __('admin.blog_posts.fields.status_draft'),
                        'published' => __('admin.blog_posts.fields.status_published'),
                        'scheduled' => __('admin.blog_posts.fields.status_scheduled'),
                        'archived' => __('admin.blog_posts.fields.status_archived'),
                    ]),

                TernaryFilter::make('is_featured')
                    ->label(__('admin.blog_posts.fields.is_featured'))
                    ->placeholder(__('All'))
                    ->trueLabel(__('admin.blog_posts.fields.is_featured'))
                    ->falseLabel(__('Inactive')),

                SelectFilter::make('category')
                    ->label(__('admin.blog_posts.fields.category'))
                    ->relationship('category', 'slug')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name),
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
            'index' => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit' => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }
}
