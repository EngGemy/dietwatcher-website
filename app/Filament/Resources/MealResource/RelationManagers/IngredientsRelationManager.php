<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealResource\RelationManagers;

use App\Models\Ingredient;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IngredientsRelationManager extends RelationManager
{
    protected static string $relationship = 'ingredients';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('quantity')
                ->numeric()
                ->label('Quantity'),

            Toggle::make('allow_print')
                ->default(true),

            Toggle::make('is_main_ingredient')
                ->default(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->getStateUsing(fn(Ingredient $record) => $record->name),

                TextColumn::make('quantity'),
                IconColumn::make('allow_print')->boolean(),
                IconColumn::make('is_main_ingredient')->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn(AttachAction $action): array => [
                        $action->getRecordSelect(),
                        TextInput::make('quantity')->numeric(),
                        Toggle::make('allow_print')->default(true),
                        Toggle::make('is_main_ingredient')->default(false),
                    ]),
            ])
            ->recordActions([
                EditAction::make(), // Edits pivot data
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
