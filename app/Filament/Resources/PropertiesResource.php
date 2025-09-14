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
use BackedEnum;

class PropertiesResource extends Resource
{
    protected static ?string $model = Property::class;

    // protected static BackedEnum|string|null $navigationIcon = IconName::HeroiconOHomeModern;

    protected static ?int $navigationSort = 2;


    protected static ?string $recordTitleAttribute = 'title';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([
            Select::make('user_id')
                ->label('Propriétaire')
                ->relationship('user', 'name')
                ->searchable()
                ->required(),
            Select::make('category_id')
                ->label('Catégorie')
                ->relationship('category', 'name')
                ->searchable(),
            Select::make('status')
                ->label('Statut')
                ->options([
                    'available' => 'Disponible',
                    'rented' => 'Loué',
                    'maintenance' => 'Maintenance',
                ])
                ->required(),
            Select::make('property_type')
                ->label('Type de bien')
                ->options([
                    'house' => 'Maison',
                    'apartment' => 'Appartement',
                    'studio' => 'Studio',
                    'villa' => 'Villa',
                    'other' => 'Autre',
                ]),
            \Filament\Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required(),
            \Filament\Forms\Components\TextInput::make('slug')
                ->label('Slug')
                ->required(),
            \Filament\Forms\Components\TextInput::make('municipality')
                ->label('Commune'),
            \Filament\Forms\Components\TextInput::make('district')
                ->label('District'),
            \Filament\Forms\Components\TextInput::make('city')
                ->label('Ville'),
            \Filament\Forms\Components\TextInput::make('price_per_night')
                ->label('Prix par nuit')
                ->numeric(),
            \Filament\Forms\Components\TextInput::make('number_of_rooms')
                ->label('Nombre de pièces')
                ->numeric(),
            \Filament\Forms\Components\Textarea::make('description')
                ->label('Description'),
            \Filament\Forms\Components\TextInput::make('longitude')
                ->label('Longitude'),
            \Filament\Forms\Components\TextInput::make('latitude')
                ->label('Latitude'),
            \Filament\Forms\Components\TextInput::make('image')
                ->label('Image (URL ou chemin)'),
            \Filament\Forms\Components\TextInput::make('features')
                ->label('Caractéristiques'),
            DatePicker::make('created_at')
                ->label('Créé le')
                ->disabled()
                ->displayFormat('d/m/Y H:i')
                ->withoutSeconds(),
            DatePicker::make('updated_at')
                ->label('Modifié le')
                ->disabled()
                ->displayFormat('d/m/Y H:i')
                ->withoutSeconds(),
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
}
