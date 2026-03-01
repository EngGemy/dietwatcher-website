<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\Widgets\PaymentStatsOverview;
use App\Models\Payment;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-credit-card';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.orders_payments');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.payments.plural_model_label');
    }

    public static function getModelLabel(): string
    {
        return __('admin.payments.model_label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.payments.plural_model_label');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Payment::where('status', PaymentStatus::PENDING)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('30s')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('order_number')
                    ->label(__('admin.payments.fields.order_number'))
                    ->fontFamily('mono')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_name')
                    ->label(__('admin.payments.fields.customer_name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('customer_email')
                    ->label(__('admin.payments.fields.customer_email'))
                    ->icon('heroicon-o-envelope')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('customer_phone')
                    ->label(__('admin.payments.fields.customer_phone'))
                    ->icon('heroicon-o-phone')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('amount')
                    ->label(__('admin.payments.fields.amount'))
                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('admin.payments.fields.status'))
                    ->badge()
                    ->color(fn (PaymentStatus $state): string => match ($state) {
                        PaymentStatus::PAID => 'success',
                        PaymentStatus::PENDING, PaymentStatus::AUTHORIZED => 'warning',
                        PaymentStatus::FAILED, PaymentStatus::EXPIRED => 'danger',
                        PaymentStatus::REFUNDED => 'gray',
                    })
                    ->formatStateUsing(fn (PaymentStatus $state): string => $state->label())
                    ->sortable(),

                TextColumn::make('payment_method')
                    ->label(__('admin.payments.fields.payment_method'))
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentMethod ? $state->label() : ($state ?? '—')),

                TextColumn::make('delivery_type')
                    ->label(__('admin.payments.fields.delivery_type'))
                    ->badge()
                    ->color(fn ($state): string => $state === 'home' ? 'success' : 'info')
                    ->sortable(),

                TextColumn::make('city')
                    ->label(__('admin.payments.fields.city'))
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('street')
                    ->label(__('admin.payments.fields.address'))
                    ->formatStateUsing(fn (Payment $record): string => implode(', ', array_filter([
                        $record->building,
                        $record->street,
                    ])) ?: '—')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('start_date')
                    ->label(__('admin.payments.fields.start_date'))
                    ->date('M d, Y')
                    ->sortable(),

                TextColumn::make('duration')
                    ->label(__('admin.payments.fields.duration'))
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                TextColumn::make('card_type')
                    ->label(__('admin.payments.fields.card_type'))
                    ->formatStateUsing(fn (Payment $record): string => implode(' ', array_filter([
                        $record->card_type ? strtoupper($record->card_type) : null,
                        $record->masked_pan,
                    ])) ?: '—')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subtotal')
                    ->label(__('admin.payments.fields.subtotal'))
                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('delivery_fee')
                    ->label(__('admin.payments.fields.delivery_fee'))
                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('discount_amount')
                    ->label(__('admin.payments.fields.discount_amount'))
                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('vat_amount')
                    ->label(__('admin.payments.fields.vat_amount'))
                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('coupon')
                    ->label(__('admin.payments.fields.coupon'))
                    ->badge()
                    ->color('success')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label(__('admin.payments.fields.created_at'))
                    ->dateTime('M d, Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.payments.filters.status'))
                    ->options(
                        collect(PaymentStatus::cases())
                            ->mapWithKeys(fn (PaymentStatus $status) => [$status->value => $status->label()])
                            ->toArray()
                    ),

                SelectFilter::make('payment_method')
                    ->label(__('admin.payments.filters.payment_method'))
                    ->options(
                        collect(PaymentMethod::cases())
                            ->mapWithKeys(fn (PaymentMethod $method) => [$method->value => $method->label()])
                            ->toArray()
                    ),

                SelectFilter::make('delivery_type')
                    ->label(__('admin.payments.filters.delivery_type'))
                    ->options([
                        'home' => __('Home Delivery'),
                        'pickup' => __('Pickup'),
                    ]),

                SelectFilter::make('city')
                    ->label(__('admin.payments.filters.city'))
                    ->options(
                        Payment::query()
                            ->whereNotNull('city')
                            ->where('city', '!=', '')
                            ->distinct()
                            ->pluck('city', 'city')
                            ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('duration')
                    ->label(__('admin.payments.fields.duration'))
                    ->options(
                        Payment::query()
                            ->whereNotNull('duration')
                            ->where('duration', '!=', '')
                            ->distinct()
                            ->pluck('duration', 'duration')
                            ->toArray()
                    ),

                Filter::make('has_coupon')
                    ->label(__('admin.payments.filters.has_coupon'))
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('coupon')),

                Filter::make('date_range')
                    ->label(__('admin.payments.filters.date_range'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('admin.payments.filters.from')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('admin.payments.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),

                Filter::make('start_date_range')
                    ->label(__('admin.payments.filters.start_date_range'))
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label(__('admin.payments.filters.from')),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label(__('admin.payments.filters.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn (Builder $q, $date) => $q->whereDate('start_date', '>=', $date))
                            ->when($data['until'], fn (Builder $q, $date) => $q->whereDate('start_date', '<=', $date));
                    }),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Status banner
                Section::make()
                    ->schema([
                        TextEntry::make('status')
                            ->label('')
                            ->badge()
                            ->size('lg')
                            ->color(fn (PaymentStatus $state): string => match ($state) {
                                PaymentStatus::PAID => 'success',
                                PaymentStatus::PENDING, PaymentStatus::AUTHORIZED => 'warning',
                                PaymentStatus::FAILED, PaymentStatus::EXPIRED => 'danger',
                                PaymentStatus::REFUNDED => 'gray',
                            })
                            ->formatStateUsing(fn (PaymentStatus $state): string => $state->label()),
                    ]),

                // 2-column: Order Info + Customer Info
                Grid::make(2)
                    ->schema([
                        Section::make(__('admin.payments.sections.order_info'))
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                TextEntry::make('order_number')
                                    ->label(__('admin.payments.fields.order_number'))
                                    ->fontFamily('mono')
                                    ->copyable(),
                                TextEntry::make('created_at')
                                    ->label(__('admin.payments.fields.created_at'))
                                    ->dateTime('M d, Y H:i'),
                                TextEntry::make('start_date')
                                    ->label(__('admin.payments.fields.start_date')),
                                TextEntry::make('duration')
                                    ->label(__('admin.payments.fields.duration')),
                                TextEntry::make('delivery_type')
                                    ->label(__('admin.payments.fields.delivery_type'))
                                    ->badge(),
                                TextEntry::make('city')
                                    ->label(__('admin.payments.fields.address'))
                                    ->formatStateUsing(fn (Payment $record): string => implode(', ', array_filter([
                                        $record->building,
                                        $record->street,
                                        $record->city,
                                    ])) ?: '—'),
                            ]),

                        Section::make(__('admin.payments.sections.customer_info'))
                            ->icon('heroicon-o-user')
                            ->schema([
                                TextEntry::make('customer_name')
                                    ->label(__('admin.payments.fields.customer_name'))
                                    ->weight('bold'),
                                TextEntry::make('customer_email')
                                    ->label(__('admin.payments.fields.customer_email'))
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                TextEntry::make('customer_phone')
                                    ->label(__('admin.payments.fields.customer_phone'))
                                    ->icon('heroicon-o-phone')
                                    ->copyable(),
                            ]),
                    ]),

                // Cart Items
                Section::make(__('admin.payments.sections.order_items'))
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        ViewEntry::make('cart_items')
                            ->label('')
                            ->view('filament.infolists.components.cart-items'),
                    ]),

                // 2-column: Pricing + Payment Details
                Grid::make(2)
                    ->schema([
                        Section::make(__('admin.payments.sections.pricing'))
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                TextEntry::make('subtotal')
                                    ->label(__('admin.payments.fields.subtotal'))
                                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR'),
                                TextEntry::make('delivery_fee')
                                    ->label(__('admin.payments.fields.delivery_fee'))
                                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR'),
                                TextEntry::make('discount_amount')
                                    ->label(__('admin.payments.fields.discount_amount'))
                                    ->formatStateUsing(fn (Payment $record): string => $record->discount_amount > 0
                                        ? '-' . number_format($record->discount_amount / 100, 2) . ' SAR' . ($record->coupon ? " ({$record->coupon})" : '')
                                        : '0.00 SAR'),
                                TextEntry::make('vat_amount')
                                    ->label(__('admin.payments.fields.vat_amount'))
                                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR'),
                                TextEntry::make('amount')
                                    ->label(__('admin.payments.fields.grand_total'))
                                    ->formatStateUsing(fn ($state): string => number_format($state / 100, 2) . ' SAR')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->color('success'),
                            ]),

                        Section::make(__('admin.payments.sections.payment_info'))
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                TextEntry::make('payment_method')
                                    ->label(__('admin.payments.fields.payment_method'))
                                    ->formatStateUsing(fn ($state): string => $state instanceof PaymentMethod ? $state->label() : ($state ?? '—')),
                                TextEntry::make('card_type')
                                    ->label(__('admin.payments.fields.card_type'))
                                    ->formatStateUsing(fn ($state): string => $state ? strtoupper($state) : '—'),
                                TextEntry::make('masked_pan')
                                    ->label(__('admin.payments.fields.masked_pan'))
                                    ->fontFamily('mono'),
                                TextEntry::make('moyasar_id')
                                    ->label(__('admin.payments.fields.moyasar_id'))
                                    ->copyable(),
                                TextEntry::make('message')
                                    ->label(__('admin.payments.fields.error_message'))
                                    ->visible(fn (Payment $record): bool => $record->status === PaymentStatus::FAILED && !empty($record->message))
                                    ->color('danger'),
                            ]),
                    ]),

                // Timestamps (collapsed)
                Section::make(__('admin.payments.sections.timestamps'))
                    ->icon('heroicon-o-clock')
                    ->collapsed()
                    ->schema([
                        TextEntry::make('created_at')
                            ->label(__('admin.payments.fields.created_at'))
                            ->dateTime('M d, Y H:i:s'),
                        TextEntry::make('updated_at')
                            ->label(__('admin.payments.fields.updated_at'))
                            ->dateTime('M d, Y H:i:s'),
                        TextEntry::make('expires_at')
                            ->label(__('admin.payments.fields.expires_at'))
                            ->dateTime('M d, Y H:i:s'),
                    ])->columns(3),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'view' => Pages\ViewPayment::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            PaymentStatsOverview::class,
        ];
    }
}
