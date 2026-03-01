<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HowItWorksStepResource\Pages;
use App\Models\HowItWorksStep;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HowItWorksStepResource extends Resource
{
    protected static ?string $model = HowItWorksStep::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-numbered-list';
    protected static ?int $navigationSort = 35;

    public static function getNavigationGroup(): ?string
    {
        return __('admin.navigation_groups.content');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FileUpload::make('image')
                ->label(__('admin.fields.image'))
                ->image()
                ->disk('public')
                ->directory('how-it-works')
                ->required(),
            
            TextInput::make('title_en')
                ->label(__('admin.fields.title_en'))
                ->required()
                ->maxLength(255),
            
            TextInput::make('title_ar')
                ->label(__('admin.fields.title_ar'))
                ->required()
                ->maxLength(255),
            
            Textarea::make('description_en')
                ->label(__('admin.fields.description_en'))
                ->required()
                ->rows(3),
            
            Textarea::make('description_ar')
                ->label(__('admin.fields.description_ar'))
                ->required()
                ->rows(3),
            
            TextInput::make('order_column')
                ->label(__('admin.fields.order'))
                ->numeric()
                ->default(0),
            
            Toggle::make('is_active')
                ->label(__('admin.fields.is_active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label(__('admin.fields.image'))
                    ->circular(),
                
                TextColumn::make('title_en')
                    ->label(__('admin.fields.title_en'))
                    ->searchable(),
                
                TextColumn::make('title_ar')
                    ->label(__('admin.fields.title_ar'))
                    ->searchable(),
                
                TextColumn::make('order_column')
                    ->label(__('admin.fields.order'))
                    ->sortable(),
                
                IconColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
            ])
            ->defaultSort('order_column')
            ->reorderable('order_column')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageHowItWorksSteps::route('/'),
        ];
    }

    public static function getModelLabel(): string
    {
        return __('admin.how_it_works.step');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.how_it_works.steps');
    }
}
