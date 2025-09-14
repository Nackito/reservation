<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertiesResource\Pages;
use App\Models\Property;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Support\Enums\IconName;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use BackedEnum;
use Illuminate\Support\Str;

class PropertiesResource extends Resource
{
    protected static ?string $model = Property::class;

    // protected static BackedEnum|string|null $navigationIcon = IconName::HeroiconOHomeModern;

    protected static ?int $navigationSort = 2;


    protected static ?string $recordTitleAttribute = 'title';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->components([
            \Filament\Schemas\Components\Section::make('Informations principales')
                ->inlineLabel()
                ->schema([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Nom')
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            $set('slug', Str::slug($state));
                        }),
                    \Filament\Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->readOnly(),
                    \Filament\Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(12),
                ])->columns(1),

            \Filament\Schemas\Components\Section::make('Caractéristiques')
                ->inlineLabel()
                ->schema([
                    \Filament\Forms\Components\CheckboxList::make('features')
                        ->label('Caractéristiques')
                        ->options([
                            'wifi' => 'Wi-Fi',
                            'parking' => 'Parking',
                            'clim' => 'Climatisation',
                            'piscine' => 'Piscine',
                            'jardin' => 'Jardin',
                            'balcon' => 'Balcon',
                            'ascenseur' => 'Ascenseur',
                            'meuble' => 'Meublé',
                            'terrasse' => 'Terrasse',
                        ]),
                ])->columns(1),
            \Filament\Schemas\Components\Section::make('Localisation')
                ->inlineLabel()
                ->schema([
                    Select::make('city')
                        ->label('Ville')
                        ->options(static::getIvoryCoastCities())
                        ->searchable()
                        ->reactive(),
                    Select::make('municipality')
                        ->label('Commune')
                        ->options([
                            'Abobo' => 'Abobo',
                            'Adjamé' => 'Adjamé',
                            'Attécoubé' => 'Attécoubé',
                            'Cocody' => 'Cocody',
                            'Koumassi' => 'Koumassi',
                            'Marcory' => 'Marcory',
                            'Plateau' => 'Plateau',
                            'Port-Bouët' => 'Port-Bouët',
                            'Treichville' => 'Treichville',
                            'Yopougon' => 'Yopougon',
                            'Songon' => 'Songon',
                            'Bingerville' => 'Bingerville',
                        ])
                        ->searchable()
                        ->visible(fn($get) => $get('city') === 'Abidjan'),
                    \Filament\Forms\Components\TextInput::make('district')->label('Quartier'),
                    \Filament\Forms\Components\TextInput::make('longitude')->label('Longitude'),
                    \Filament\Forms\Components\TextInput::make('latitude')->label('Latitude'),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Détails')
                ->inlineLabel()
                ->schema([
                    \Filament\Forms\Components\TextInput::make('price_per_night')->label('Prix par nuit')->numeric(),
                    \Filament\Forms\Components\TextInput::make('number_of_rooms')->label('Nombre de pièces')->numeric(),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Statut & Catégorie')
                ->inlineLabel()
                ->schema([
                    Select::make('user_id')
                        ->label('Propriétaire')
                        ->relationship('user', 'name', fn($query) => $query->orderBy('name'))
                        ->searchable()
                        ->preload()
                        ->required(),
                    Select::make('category_id')->label('Catégorie')->relationship('category', 'name')->searchable(),
                    Select::make('status')->label('Statut')->options([
                        'available' => 'Disponible',
                        'rented' => 'Loué',
                        'maintenance' => 'Maintenance',
                    ])->required(),
                    Select::make('property_type')->label('Type de bien')->options([
                        'house' => 'Maison',
                        'apartment' => 'Appartement',
                        'studio' => 'Studio',
                        'villa' => 'Villa',
                        'other' => 'Autre',
                    ]),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Dates système')
                ->inlineLabel()
                ->schema([
                    DatePicker::make('created_at')->label('Créé le')->disabled()->displayFormat('d/m/Y H:i')->withoutSeconds(),
                    DatePicker::make('updated_at')->label('Modifié le')->disabled()->displayFormat('d/m/Y H:i')->withoutSeconds(),
                ])->columns(2),
            \Filament\Schemas\Components\Section::make('Images')
                ->inlineLabel()
                ->schema([
                    \Filament\Forms\Components\FileUpload::make('image')
                        ->label('Images')
                        ->image()
                        ->multiple()
                        ->directory('properties/images')
                        ->disk('public')
                        ->visibility('public')
                        ->imageEditor()
                        ->openable()
                        ->downloadable(),
                ])->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Owner')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('usd', true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'available' => 'Available',
                        'rented' => 'Rented',
                        'maintenance' => 'Maintenance',
                    ]),
                Tables\Filters\Filter::make('created_from')
                    ->form([
                        DatePicker::make('created_from')->label('Created From'),
                        DatePicker::make('created_until')->label('Created Until'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when($data['created_from'], fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
                Action::make('view')
                    ->label('Afficher')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn(Property $record) => static::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
                Action::make('edit')
                    ->label('Modifier')
                    ->icon('heroicon-m-pencil-square')
                    ->color('primary')
                    ->url(fn(Property $record) => static::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Créer')
                    ->icon('heroicon-m-plus')
                    ->color('success')
                    ->url(route('filament.admin.resources.properties.create'))
                    ->button(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProperties::route('/'),
            'create' => Pages\CreateProperties::route('/create'),
            'edit' => Pages\EditProperties::route('/{record}/edit'),
            'view' => Pages\ViewProperties::route('/{record}'),
        ];
    }
    /*public static function canAccessPanel(): bool
    {
        return Auth::user() && Auth::user()->email === 'admin@example.com';
    }*/

    /**
     * Retourne la liste unique des villes de Côte d'Ivoire pour le champ Select.
     */
    protected static function getIvoryCoastCities(): array
    {
        return [
            'Abidjan' => 'Abidjan',
            'Yamoussoukro' => 'Yamoussoukro',
            'Bouaké' => 'Bouaké',
            'Daloa' => 'Daloa',
            'San Pedro' => 'San Pedro',
            'Korhogo' => 'Korhogo',
            'Man' => 'Man',
            'Gagnoa' => 'Gagnoa',
            'Abengourou' => 'Abengourou',
            'Agboville' => 'Agboville',
            'Divo' => 'Divo',
            'Anyama' => 'Anyama',
            'Bondoukou' => 'Bondoukou',
            'Séguéla' => 'Séguéla',
            'Odienné' => 'Odienné',
            'Ferkessédougou' => 'Ferkessédougou',
            'Sinfra' => 'Sinfra',
            'Issia' => 'Issia',
            'Sassandra' => 'Sassandra',
            'Toumodi' => 'Toumodi',
            'Soubré' => 'Soubré',
            'Aboisso' => 'Aboisso',
            'Grand-Bassam' => 'Grand-Bassam',
            'Dabou' => 'Dabou',
            'Bingerville' => 'Bingerville',
            'Adzopé' => 'Adzopé',
            'Bouaflé' => 'Bouaflé',
            'Daoukro' => 'Daoukro',
            'Touba' => 'Touba',
            'Vavoua' => 'Vavoua',
            'Guiglo' => 'Guiglo',
            'Danané' => 'Danané',
            'Tiassalé' => 'Tiassalé',
            'Akoupé' => 'Akoupé',
            'Tabou' => 'Tabou',
            'Lakota' => 'Lakota',
            'Bouna' => 'Bouna',
            'Tanda' => 'Tanda',
            'Mankono' => 'Mankono',
            'Béoumi' => 'Béoumi',
            'Dimbokro' => 'Dimbokro',
            'Tiébissou' => 'Tiébissou',
            'Arrah' => 'Arrah',
            'Jacqueville' => 'Jacqueville',
            'Katiola' => 'Katiola',
            'Zuénoula' => 'Zuénoula',
            'Bangolo' => 'Bangolo',
            'Grand-Lahou' => 'Grand-Lahou',
            'Sakassou' => 'Sakassou',
            'Bocanda' => 'Bocanda',
            'Agnibilékrou' => 'Agnibilékrou',
            'Djekanou' => 'Djekanou',
            'Koun-Fao' => 'Koun-Fao',
            'Prikro' => 'Prikro',
            'Oumé' => 'Oumé',
            'Guitry' => 'Guitry',
            'Samatiguila' => 'Samatiguila',
            'Minignan' => 'Minignan',
            'Koro' => 'Koro',
            'Kouassi-Kouassikro' => 'Kouassi-Kouassikro',
            'Kouibly' => 'Kouibly',
            'Kouassi-Datékro' => 'Kouassi-Datékro',
        ];
    }
}
