<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PropertiesResource\Pages;
use App\Filament\Resources\PropertiesResource\RelationManagers;
use App\Models\Property;
use Filament\Forms;
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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PropertiesResource extends Resource
{
    protected static ?string $model = Property::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
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
                            ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Str::slug($state));
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
                            ->multiple()
                            ->directory('products')
                            ->maxFiles(10)
                            ->reorderable()
                        //->afterStateUpdated(function ($state, $set, $get) {
                        //Stockez les images temporairement dans le dossier public
                        //    $set('image', $state);
                        //}),
                    ])
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('Property Details')->schema([
                        Forms\Components\TextInput::make('price_per_night')
                            ->required()
                            ->numeric()
                            ->placeholder('100.00'),

                        Forms\Components\TextInput::make('city')
                            ->required()
                            ->placeholder('Abidjan'),

                        Forms\Components\TextInput::make('district')
                            ->required()
                            ->placeholder('Cocody 9e Tranche'),

                        Forms\Components\Hidden::make('user_id')
                            ->default(fn() => Auth::id()),
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
                                'wifi' => 'Wi-Fi',
                                'parking' => 'Parking',
                                'pool' => 'Pool',
                                'gym' => 'Gym',
                                'kitchen' => 'Kitchen',
                                'air_conditioning' => 'Air Conditioning',
                                'breakfast' => 'Breakfast',
                                'Canal+' => 'Canal+',
                                'TV' => 'TV',
                                'Netflix' => 'Netflix',
                                'Youtube' => 'Youtube',
                                'garden' => 'Garden',
                                'balcony' => 'Balcony',
                                'Playstation' => 'Playstation',
                                'hot water' => 'Hot Water',
                                'Generator' => 'Generator',
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
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable(),

                Tables\Columns\ImageColumn::make('image'),

                Tables\Columns\TextColumn::make('description')
                    ->searchable(),

                Tables\Columns\TextColumn::make('price_per_night')
                    ->searchable(),
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
        return parent::getEloquentQuery()
            ->where('user_id', Auth::id());
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
    }
}
