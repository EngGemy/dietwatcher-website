<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanResource\RelationManagers;

use App\Models\MealType;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MealTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'mealTypes';

    protected static ?string $title = 'Meal Types';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Meal Type')
                    ->getStateUsing(fn(MealType $record) => $record->translate(app()->getLocale())?->name
                        ?? $record->translate('en')?->name
                        ?? '—')
                    ->searchable(),

                TextColumn::make('slug')
                    ->label('Slug'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ]);
    }
}
