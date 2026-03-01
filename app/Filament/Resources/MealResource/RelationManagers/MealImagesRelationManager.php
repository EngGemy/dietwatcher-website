<?php

declare(strict_types=1);

namespace App\Filament\Resources\MealResource\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MealImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'images';

    protected static ?string $title = 'Gallery Images';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image_path')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('meals/gallery')
                ->required(),

            Toggle::make('is_cover')
                ->label('Cover')
                ->helperText('Setting this will unset other cover images for this meal.')
                ->default(false),

            Toggle::make('is_active')
                ->default(true),

            TextInput::make('order_column')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_column')->sortable(),
                ImageColumn::make('image_path')->label('Image'),
                IconColumn::make('is_cover')->boolean(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
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
