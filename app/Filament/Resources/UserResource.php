<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\UserResource\RelationManagers\BookingsRelationManager;
use App\Filament\Resources\UserResource\RelationManagers\PropertiesRelationManager;
use App\Models\Booking;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Si l'utilisateur est admin, afficher un message et masquer les champs éditables
                Forms\Components\Group::make([
                    Forms\Components\Placeholder::make('admin_notice')
                        ->content('Les informations de l\'administrateur ne peuvent pas être modifiées ici.')
                ])->visible(fn($record) => $record && $record->role === 'admin'),
                Forms\Components\TextInput::make('name')
                    ->label('Nom')
                    ->required()
                    ->placeholder('John Doe')
                    ->disabled(fn($record) => $record && $record->role === 'admin'),

                Forms\Components\TextInput::make('firstname')
                    ->label('Prénom')
                    ->required()
                    ->placeholder('Jean')
                    ->disabled(fn($record) => $record && $record->role === 'admin'),

                Forms\Components\Select::make('country_code')
                    ->label('Indicatif pays')
                    ->options([
                        '+225' => '🇨🇮 +225',
                        '+33' => '🇫🇷 +33',
                        '+226' => '🇧🇫 +226',
                        '+229' => '🇧🇯 +229',
                        '+223' => '🇲🇱 +223',
                        '+221' => '🇸🇳 +221',
                        '+1' => '🇺🇸 +1',
                    ])
                    ->default('+225')
                    ->searchable()
                    ->disabled(fn($record) => $record && $record->role === 'admin'),

                Forms\Components\TextInput::make('phone')
                    ->label('Téléphone')
                    ->placeholder('0102030405')
                    ->disabled(fn($record) => $record && $record->role === 'admin'),

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required()
                    ->disabled(fn($record) => $record && $record->role === 'admin'),

                Forms\Components\Select::make('role')
                    ->label('Rôle')
                    ->options([
                        'admin' => 'Admin',
                        'user' => 'Utilisateur',
                        'employe' => 'Employé',
                        'proprietaire' => 'Propriétaire',
                        'gestionnaire' => 'Gestionnaire',
                    ])
                    ->required()
                    ->default('user')
                    ->disabled(fn($record) => $record && $record->role === 'admin'),

                Forms\Components\DateTimePicker::make('email_verified_at')
                    ->label('Email Verified At')
                    ->default(now())
                    ->disabled(fn($record) => $record && $record->role === 'admin'),

                Forms\Components\TextInput::make('password')
                    ->label('Password')
                    ->password()
                    ->autocomplete('new-password')
                    ->placeholder('********')
                    ->required(fn($context) => $context === 'create')
                    ->dehydrated(fn($state) => filled($state)),

                Forms\Components\TextInput::make('password_confirmation')
                    ->label('Password Confirmation')
                    ->password()
                    ->placeholder('********')
                    ->required(fn($context) => $context === 'create')
                    ->dehydrated(fn($state) => filled($state)),
            ]);
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

    // Si je souhaite désactiver la navigation pour cette ressource, décommenter la ligne ci-dessous
    /*public static function shouldRegisterNavigation(): bool
    {
        return false;
    }*/
}
