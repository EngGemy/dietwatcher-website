<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\CouponResource\Pages;
use App\Models\Coupon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?int $navigationSort = 70;

    public static function getNavigationGroup(): ?string
    {
        return __('Marketing');
    }

    public static function getNavigationLabel(): string
    {
        return __('Coupons');
    }

    public static function getModelLabel(): string
    {
        return __('Coupon');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Coupons');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Coupon Details'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('code')
                            ->label(__('Code'))
                            ->required()
                            ->maxLength(50)
                            ->unique(ignoreRecord: true)
                            ->dehydrateStateUsing(fn(string $state) => strtoupper($state))
                            ->suffixAction(
                                \Filament\Actions\Action::make('generate')
                                    ->icon('heroicon-m-sparkles')
                                    ->action(fn($set) => $set('code', strtoupper(Str::random(8))))
                            ),

                        Select::make('type')
                            ->label(__('Discount Type'))
                            ->options([
                                'percentage' => __('Percentage'),
                                'fixed' => __('Fixed Amount'),
                            ])
                            ->required()
                            ->default('percentage'),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('value')
                            ->label(__('Value'))
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->helperText(__('For percentage: enter percentage (e.g. 10 for 10%). For fixed: enter amount in halalas (e.g. 2500 for 25 SAR).')),

                        TextInput::make('min_order_amount')
                            ->label(__('Min Order Amount (halalas)'))
                            ->numeric()
                            ->nullable()
                            ->helperText(__('Minimum order subtotal in halalas. Leave empty for no minimum.')),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('max_discount_amount')
                            ->label(__('Max Discount Amount (halalas)'))
                            ->numeric()
                            ->nullable()
                            ->helperText(__('Maximum discount in halalas. Useful for percentage coupons.')),

                        Toggle::make('is_active')
                            ->label(__('Active'))
                            ->default(true),
                    ]),
                ]),

            Section::make(__('Usage Limits'))
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('max_uses')
                            ->label(__('Max Total Uses'))
                            ->numeric()
                            ->nullable()
                            ->helperText(__('Leave empty for unlimited uses.')),

                        TextInput::make('max_uses_per_user')
                            ->label(__('Max Uses Per User'))
                            ->numeric()
                            ->nullable()
                            ->helperText(__('Leave empty for unlimited per user.')),
                    ]),
                ]),

            Section::make(__('Schedule'))
                ->schema([
                    Grid::make(2)->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('Starts At'))
                            ->nullable(),

                        DateTimePicker::make('expires_at')
                            ->label(__('Expires At'))
                            ->nullable(),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('Code'))
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),

                TextColumn::make('type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn(string $state) => $state === 'percentage' ? __('Percentage') : __('Fixed'))
                    ->color(fn(string $state) => $state === 'percentage' ? 'info' : 'success'),

                TextColumn::make('value')
                    ->label(__('Value'))
                    ->formatStateUsing(function (Coupon $record) {
                        if ($record->type === 'percentage') {
                            return $record->value . '%';
                        }
                        return number_format($record->value / 100, 2) . ' SAR';
                    }),

                TextColumn::make('used_count')
                    ->label(__('Used / Max'))
                    ->formatStateUsing(fn(Coupon $record) => $record->used_count . ' / ' . ($record->max_uses ?? '∞')),

                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),

                TextColumn::make('expires_at')
                    ->label(__('Expires'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('Never')),

                TextColumn::make('created_at')
                    ->label(__('Created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('is_active')
                    ->label(__('Active only'))
                    ->query(fn($q) => $q->where('is_active', true)),
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
            ->emptyStateHeading(__('No coupons yet'))
            ->emptyStateDescription(__('Create your first coupon to get started.'));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoupons::route('/'),
            'create' => Pages\CreateCoupon::route('/create'),
            'edit' => Pages\EditCoupon::route('/{record}/edit'),
        ];
    }
}
