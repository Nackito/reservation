<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\PropertiesRelationManager;
use App\Models\Booking;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\User\Schemas\UserForm;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use BackedEnum;

use Filament\Support\Enums\IconName;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string | BackedEnum | null $navigationIcon = "heroicon-o-user-group";
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required(),
                TextInput::make('firstname')->required(),
                TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->required(),
                Forms\Components\Select::make('country_code')
                    ->label('Code pays')
                    ->options([
                        '+225' => '+225 (Côte d\'Ivoire)',
                        '+33' => '+33 (France)',
                        '+221' => '+221 (Sénégal)',
                        '+226' => '+226 (Burkina Faso)',
                        '+229' => '+229 (Bénin)',
                        '+1' => '+1 (USA/Canada)',
                        // Ajoute d'autres si besoin
                    ])
                    ->searchable()
                    ->required(),
                TextInput::make('phone')->tel()->label('Téléphone')->required(),
                TextInput::make('password')->password()->required()->minLength(8)->maxLength(255),
                // ...
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('firstname')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->searchable(),
            ])
            ->filters([
                //...
            ])
            ->actions([
                CreateAction::make()
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        // ...
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            BookingsRelationManager::class,
            PropertiesRelationManager::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'email',
            'firstname',
            'phone',
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    // Désactive la navigation pour cette ressource (Filament 4)
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
}
