<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\ContactResource\Pages;
use App\Models\Contact;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContactResource extends Resource
{
    protected static ?string $model = Contact::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.contacts.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.contacts.navigation_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.contacts.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.contacts.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::new()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->label(__('admin.contacts.fields.first_name'))
                    ->disabled(),

                TextInput::make('last_name')
                    ->label(__('admin.contacts.fields.last_name'))
                    ->disabled(),

                TextInput::make('email')
                    ->label(__('admin.contacts.fields.email'))
                    ->disabled(),

                TextInput::make('subject')
                    ->label(__('admin.contacts.fields.subject'))
                    ->disabled(),

                Textarea::make('message')
                    ->label(__('admin.contacts.fields.message'))
                    ->disabled()
                    ->columnSpanFull(),

                Select::make('status')
                    ->label(__('admin.contacts.fields.status'))
                    ->options([
                        'new' => __('admin.contacts.status.new'),
                        'read' => __('admin.contacts.status.read'),
                        'replied' => __('admin.contacts.status.replied'),
                    ])
                    ->required(),

                Textarea::make('admin_notes')
                    ->label(__('admin.contacts.fields.admin_notes'))
                    ->rows(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label(__('admin.contacts.fields.full_name'))
                    ->searchable(['first_name', 'last_name']),

                TextColumn::make('email')
                    ->label(__('admin.contacts.fields.email'))
                    ->searchable()
                    ->copyable(),

                TextColumn::make('subject')
                    ->label(__('admin.contacts.fields.subject'))
                    ->limit(30),

                BadgeColumn::make('status')
                    ->label(__('admin.contacts.fields.status'))
                    ->colors([
                        'danger' => 'new',
                        'warning' => 'read',
                        'success' => 'replied',
                    ])
                    ->formatStateUsing(fn (string $state): string => __("admin.contacts.status.{$state}")),

                TextColumn::make('created_at')
                    ->label(__('admin.contacts.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.contacts.fields.status'))
                    ->options([
                        'new' => __('admin.contacts.status.new'),
                        'read' => __('admin.contacts.status.read'),
                        'replied' => __('admin.contacts.status.replied'),
                    ]),

                Filter::make('unread')
                    ->label(__('admin.contacts.filters.unread'))
                    ->query(fn ($query) => $query->whereNull('read_at')),
            ])
            ->recordActions([
                Action::make('reply')
                    ->label(__('admin.contacts.actions.reply'))
                    ->icon('heroicon-o-paper-airplane')
                    ->url(fn (Contact $record): string => "mailto:{$record->email}")
                    ->openUrlInNewTab()
                    ->color('success'),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading(__('admin.contacts.empty_state.heading'))
            ->emptyStateDescription(__('admin.contacts.empty_state.description'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListContacts::route('/'),
            'edit' => Pages\EditContact::route('/{record}/edit'),
        ];
    }
}
