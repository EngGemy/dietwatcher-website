<?php

declare(strict_types=1);

namespace App\Filament\Resources\PlanResource\RelationManagers;

use App\Models\PlanImage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;

class PlanImagesRelationManager extends RelationManager
{
    protected static string $relationship = 'galleryImages';

    protected static ?string $title = 'Gallery Images';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image_path')
                ->label('Image')
                ->image()
                ->disk('public')
                ->directory('plans/gallery')
                ->required(),

            Toggle::make('is_cover')
                ->label('Cover Image')
                ->helperText('Only one cover image per plan')
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
                ImageColumn::make('image_path')
                    ->label('Image')
                    ->square(),

                IconColumn::make('is_cover')
                    ->label('Cover')
                    ->boolean(),

                IconColumn::make('is_active')
                    ->label('Active')
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
