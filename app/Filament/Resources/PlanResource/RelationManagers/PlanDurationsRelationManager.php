<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanResource\RelationManagers;

use App\Models\Offer;
use App\Models\PlanDuration;
use App\Models\Service;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanDurationsRelationManager extends RelationManager
{
    protected static string $relationship = 'durations';

    protected static ?string $title = 'Duration & Pricing';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('days')
                ->label('Days')
                ->numeric()
                ->required()
                ->minValue(1),

            TextInput::make('price')
                ->label('Price')
                ->numeric()
                ->required()
                ->minValue(0)
                ->prefix('SAR'),

            TextInput::make('delivery_price')
                ->label('Delivery Price')
                ->numeric()
                ->default(0)
                ->minValue(0)
                ->prefix('SAR'),

            Select::make('service_id')
                ->label('Service')
                ->options(Service::pluck('name', 'id'))
                ->searchable()
                ->nullable(),

            Select::make('offers')
                ->label('Offers')
                ->multiple()
                ->relationship('offers', 'name')
                ->preload()
                ->searchable(),

            DatePicker::make('start_date')
                ->label('Start Date')
                ->nullable(),

            TextInput::make('currency')
                ->label('Currency')
                ->default('SAR')
                ->maxLength(3),

            Toggle::make('is_default')
                ->label('Default Duration')
                ->helperText('Only one default per plan')
                ->default(false),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('days')
                    ->label('Days')
                    ->suffix(' days')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Price')
                    ->money('SAR')
                    ->sortable(),

                TextColumn::make('delivery_price')
                    ->label('Delivery')
                    ->money('SAR'),

                TextColumn::make('total_display')
                    ->label('Total (with VAT)')
                    ->getStateUsing(fn(PlanDuration $record) => 'SAR ' . number_format($record->total, 2)),

                TextColumn::make('service.name')
                    ->label('Service')
                    ->default('—'),

                TextColumn::make('offers_count')
                    ->label('Offers')
                    ->counts('offers'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->defaultSort('days')
            ->headerActions([
                CreateAction::make(),
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
}
