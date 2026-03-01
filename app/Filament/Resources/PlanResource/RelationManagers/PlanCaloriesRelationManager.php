<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanResource\RelationManagers;

use App\Models\PlanCalorie;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanCaloriesRelationManager extends RelationManager
{
    protected static string $relationship = 'calories';

    protected static ?string $title = 'Calorie Options';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('min_amount')
                ->label('Min Calories')
                ->numeric()
                ->required()
                ->minValue(0),

            TextInput::make('max_amount')
                ->label('Max Calories')
                ->numeric()
                ->required()
                ->minValue(0),

            Toggle::make('is_default')
                ->label('Default Option')
                ->helperText('Only one default per plan')
                ->default(false),

            Repeater::make('macros')
                ->relationship('macros')
                ->label('Macro Nutritional Information')
                ->schema([
                    TextInput::make('calories')
                        ->label('Calories')
                        ->numeric()
                        ->required(),

                    TextInput::make('protein_g')
                        ->label('Protein (g)')
                        ->numeric()
                        ->required(),

                    TextInput::make('carbs_g')
                        ->label('Carbs (g)')
                        ->numeric()
                        ->required(),

                    TextInput::make('fat_g')
                        ->label('Fat (g)')
                        ->numeric()
                        ->required(),
                ])
                ->columnSpanFull()
                ->defaultItems(1)
                ->maxItems(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('range_text')
                    ->label('Calorie Range')
                    ->getStateUsing(fn(PlanCalorie $record) => "{$record->min_amount}-{$record->max_amount} kcal"),

                TextColumn::make('macros.calories')
                    ->label('Calories')
                    ->default('—'),

                TextColumn::make('macros.protein_g')
                    ->label('Protein (g)')
                    ->default('—'),

                TextColumn::make('macros.carbs_g')
                    ->label('Carbs (g)')
                    ->default('—'),

                TextColumn::make('macros.fat_g')
                    ->label('Fat (g)')
                    ->default('—'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
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
