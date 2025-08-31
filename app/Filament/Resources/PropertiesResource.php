<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertiesResource\Pages;
use App\Filament\Resources\PropertiesResource\RelationManagers;
use App\Models\Property;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Group;
use Filament\Forms\Set;
use Filament\Support\Markdown;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use function Laravel\Prompts\search;

class PropertiesResource extends Resource
{
    protected static ?string $model = Property::class;
    /*protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Property Information')->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, Set $set) {
                                $set('slug', Str::slug($state)); // Met à jour le slug à chaque modification du nom
                            }),

                        Forms\Components\TextInput::make('slug')
                            ->maxLength(255)
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->unique(Property::class, 'slug', ignoreRecord: true),

                        MarkdownEditor::make('description')
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('properties')
                            ->maxLength(65535)
                            ->placeholder('A beautiful villa in the countryside.'),
                    ])->columns(2),

                    Section::make('Images')->schema([
                        Forms\Components\FileUpload::make('images')
                            ->image()
                            ->multiple()
                            ->directory('properties')
                            ->maxFiles(10)
                            ->reorderable()
                            ->preserveFilenames() // Conserve les noms de fichiers d'origine
                            ->helperText('Upload images of the property. You can upload multiple images.')
                        //->afterStateUpdated(function ($state, $set, $get) {
                        //Stockez les images temporairement dans le dossier public
                        //    $set('image', $state);
                        //}),
                    ])
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('Property Details')->schema([
                        Forms\Components\TextInput::make('price_per_night')
                            ->label('Prix par nuit (FrCFA)')
                            ->required()
                            ->numeric()
                            ->placeholder('10000'),

                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->placeholder('Abidjan'),

                        Forms\Components\TextInput::make('municipality')
                            ->required()
                            ->placeholder('Cocody'),

                        Forms\Components\TextInput::make('district')
                            ->required()
                            ->placeholder('Cocody 9e Tranche'),

                        Forms\Components\Select::make('property_type')
                            ->options([
                                'apartment' => 'Appartement',
                                'house' => 'Maison',
                                'duplex house' => 'Maison Duplex',
                                'studio' => 'Studio',
                            ])
                            ->required()
                            ->placeholder('Select property type'),

                        Forms\Components\TextInput::make('number_of_rooms')
                            ->numeric()
                            ->required()
                            ->minValue(2)
                            ->maxValue(10)
                            ->placeholder('3')
                            ->visible(fn($get) => $get('property_type') !== 'studio'),

                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->default(fn() => Auth::id())
                            ->label('Utilisateur propriétaire'),
                    ]),
                    Section::make('Status')->schema([
                        Forms\Components\Select::make('status')
                            ->options([
                                'available' => 'Available',
                                'booked' => 'Booked',
                                'pending' => 'Pending',
                            ])
                            ->default('available')
                            ->required()
                            ->placeholder('Select status'),

                        Forms\Components\CheckboxList::make('features')
                            ->options([
                                'WiFi' => 'Wi-Fi',
                                'Parking gratuit' => 'Parking',
                                'Piscine' => 'Pool',
                                'Salle de sport' => 'Gym',
                                'Cuisine' => 'Kitchen',
                                'Climatisation' => 'Air Conditioning',
                                'Petit déjeuné' => 'Breakfast',
                                'Canal+' => 'Canal+',
                                'TV' => 'TV',
                                'Netflix' => 'Netflix',
                                'Youtube' => 'Youtube',
                                'Jardin' => 'Garden',
                                'Balcon' => 'Balcony',
                                'Playstation' => 'Playstation',
                                'Eau chaude' => 'Hot Water',
                                'Groupe électrogène' => 'Generator',
                                'Sécurité 24/7' => '24/7 Security',
                                'Animaux acceptés' => 'Pets Allowed',
                                'Jacuzzi' => 'Jacuzzi',
                                'Barbecue' => 'Barbecue',
                                'Lave-linge' => 'Washing Machine',
                                'Sèche-linge' => 'Dryer',
                                'Fer à repasser' => 'Iron',
                                'Sèche-cheveux' => 'Hair Dryer',
                                'Chauffage' => 'Heating',
                                'Coffre-fort' => 'Safe',
                                'Réveil' => 'Alarm',
                                'Ascenseur' => 'Elevator',
                                'Terrasse' => 'Terrace',
                                'Ventilateur' => 'Fan',
                                'Télévision' => 'Television',
                            ])
                            ->columns(2)
                            ->label('Select features'),
                    ])
                ])->columnSpan(1)

                /*Section::make([
                        Grid::make()
                            

                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->required()
                                    ->placeholder('A beautiful villa in the countryside.'),

                                Forms\Components\TextInput::make('price_per_night')
                                    ->label('Price per night')
                                    ->required()
                                    ->placeholder('100.00'),

                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->required()
                                    ->placeholder('Abidjan'),

                                Forms\Components\TextInput::make('district')
                                    ->label('District')
                                    ->required()
                                    ->placeholder('Cocody 9e Tranche'),


                                Forms\Components\FileUpload::make('image')
                                    ->multiple()
                                    ->label('Image')
                                    ->image()
                                    ->required(),

                                Forms\Components\Hidden::make('user_id')
                                    ->default(fn() => Auth::id()),
                            ])
                    ])*/
    /*])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(15)
                    ->toggleable(),

                ImageColumn::make('images.image_path')
                    ->label('Images')
                    ->circular()
                    ->stacked()
                    ->limit(3)
                    ->toggleable()
                    ->limitedRemainingText(),

                Tables\Columns\TextColumn::make('price_per_night')
                    ->searchable(),

                Tables\Columns\TextColumn::make('property_type')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('number_of_rooms')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('city')
                    ->searchable(),

                Tables\Columns\TextColumn::make('municipality')
                    ->searchable(),

                Tables\Columns\TextColumn::make('district')
                    ->searchable(),

                Tables\Columns\SelectColumn::make('status')
                    ->options([
                        'available' => 'Disponible',
                        'booked' => 'Occupé',
                        'pending' => 'En attente',
                    ])
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label('Created At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->label('Created At')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
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
        ];
    }*/
}
