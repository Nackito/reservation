<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoomTypeResource\Pages;
use App\Models\RoomType;
use App\Models\Property;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use BackedEnum;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    private const HOTEL_FR = 'Hôtel';
    private const HOTEL_EN = 'Hotel';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([
            Select::make('property_id')
                ->label(self::HOTEL_FR)
                ->options(function () {
                    // Limite aux propriétés dont la catégorie est "Hôtel"
                    return Property::query()
                        ->with('category')
                        ->get()
                        ->filter(function ($p) {
                            $name = optional($p->category)->name;
                            return $name === self::HOTEL_FR || $name === self::HOTEL_EN;
                        })
                        ->pluck('name', 'id');
                })
                ->searchable()
                ->required(),
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
                ->label('Images du type')
                ->image()
                ->multiple()
                ->directory('room-types')
                ->disk('public')
                ->preserveFilenames(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('property.name')->label('Hôtel')->searchable()->sortable(),
                TextColumn::make('name')->label('Type')->searchable()->sortable(),
                TextColumn::make('capacity')->label('Cap.'),
                TextColumn::make('beds')->label('Lits'),
                TextColumn::make('inventory')->label('Stock'),
                TextColumn::make('price_per_night')->label('Prix/nuit')->money('XOF', true),
            ])
            ->filters([
                // On pourrait ajouter un filtre pour un hôtel spécifique si besoin
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Pas de relations imbriquées ici
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoomTypes::route('/'),
            'create' => Pages\CreateRoomType::route('/create'),
            'edit' => Pages\EditRoomType::route('/{record}/edit'),
        ];
    }
}
