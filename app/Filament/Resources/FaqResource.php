<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static string|\UnitEnum|null $navigationGroup = 'admin.navigation_groups.faq';

    protected static ?int $navigationSort = 80;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.faq');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.faqs.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.faqs.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.faqs.plural_model_label');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = config('app.available_locales', ['en' => 'English', 'ar' => 'العربية']);
        $defaultLocale = config('app.fallback_locale', 'en');

        $tabs = [];
        foreach ($locales as $code => $label) {
            $tabs[] = Tabs\Tab::make($label)
                ->schema([
                    TextInput::make("translations.{$code}.question")
                        ->label(__('admin.faqs.fields.question') . " ($code)")
                        ->required($code === $defaultLocale),
                    RichEditor::make("translations.{$code}.answer")
                        ->label(__('admin.faqs.fields.answer') . " ($code)")
                        ->required($code === $defaultLocale),
                ]);
        }

        return $schema->components([
            Tabs::make(__('admin.common.translations'))
                ->tabs($tabs)
                ->columnSpanFull(),

            Select::make('faq_category_id')
                ->relationship('category', 'id')
                ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                ->label(__('admin.faqs.fields.category'))
                ->searchable()
                ->preload(),

            TextInput::make('order_column')
                ->label(__('admin.faqs.fields.order_column'))
                ->numeric()
                ->default(0),

            Toggle::make('is_active')
                ->label(__('admin.faqs.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_column')
                    ->label(__('admin.faqs.fields.order_column'))
                    ->sortable(),

                TextColumn::make('question')
                    ->label(__('admin.faqs.fields.question'))
                    ->getStateUsing(fn(Faq $record) => $record->question)
                    ->searchable(),

                TextColumn::make('category.name')
                    ->label(__('admin.faqs.fields.category'))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label(__('admin.faqs.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->filters([
                Filter::make('is_active')
                    ->label(__('admin.faqs.fields.is_active'))
                    ->query(fn($q) => $q->where('is_active', true)),
                SelectFilter::make('category')
                    ->relationship('category', 'id')
                    ->getOptionLabelFromRecordUsing(fn($record) => $record->name)
                    ->label(__('admin.faqs.fields.category')),
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
            ->emptyStateHeading(__('admin.faqs.empty_state.heading'))
            ->emptyStateDescription(__('admin.faqs.empty_state.description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
