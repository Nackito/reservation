<?php

namespace App\Filament\Resources\PropertiesResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;

class RoomTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'roomTypes';

    public function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom du type')->required(),
            TextInput::make('capacity')->label('Capacité')->numeric()->minValue(1)->default(1),
            TextInput::make('beds')->label('Lits')->numeric()->minValue(1)->default(1),
            TextInput::make('price_per_night')->label('Prix/nuit (optionnel)')->numeric()->minValue(0),
            TextInput::make('inventory')->label('Inventaire')->numeric()->minValue(1)->default(1),
            CheckboxList::make('amenities')
                ->label('Équipements')
                ->options(\App\Models\Property::FEATURES)
                ->columns(2),
            Textarea::make('description')->label('Description')->rows(4),
            FileUpload::make('images')
                ->label('Images')
                ->image()
                ->multiple()
                ->directory('room-types')
                ->disk('public')
                ->preserveFilenames(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Type'),
                TextColumn::make('capacity')->label('Capacité'),
                TextColumn::make('beds')->label('Lits'),
                TextColumn::make('price_per_night')->label('Prix/nuit')->money('XOF', true),
                TextColumn::make('inventory')->label('Inventaire'),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
