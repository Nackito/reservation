<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Reviews;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Reviews::class;

    /*protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Avis';

    protected static ?string $pluralLabel = 'Avis';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('user_id')
                    ->label('Utilisateur')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('property_id')
                    ->label('Propriété')
                    ->required()
                    ->numeric(),
                Forms\Components\Textarea::make('review')
                    ->label('Avis')
                    ->required(),
                Forms\Components\TextInput::make('rating')
                    ->label('Note')
                    ->required()
                    ->numeric()
                    ->min(1)
                    ->max(5),
                Forms\Components\Toggle::make('approved')
                    ->label('Approuvé')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Utilisateur')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Propriété')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('review')
                    ->label('Avis')
                    ->limit(50),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Note')
                    ->sortable(),
                Tables\Columns\BooleanColumn::make('approved')
                    ->label('Approuvé')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('approved')
                    ->label('Approuvé')
                    ->query(fn(Builder $query) => $query->where('approved', true)),
                Tables\Filters\Filter::make('pending')
                    ->label('En attente')
                    ->query(fn(Builder $query) => $query->where('approved', false)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approuver')
                    ->action(function (Reviews $record) {
                        $record->update(['approved' => true]);
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn(Reviews $record) => !$record->approved),
                Tables\Actions\Action::make('disapprove')
                    ->label('Invalider')
                    ->action(function (Reviews $record) {
                        $record->update(['approved' => false]);
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn(Reviews $record) => $record->approved),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }*/
}
