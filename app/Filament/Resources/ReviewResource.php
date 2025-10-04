<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Reviews;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use \Filament\Schemas\Schema;

class ReviewResource extends Resource
{
    protected static ?string $model = Reviews::class;

    private const APPROVED_LABEL = 'Approuvé';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Avis';

    protected static ?string $pluralLabel = 'Avis';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('user_id')
                ->relationship('user', 'name')
                ->label('Utilisateur')
                ->required(),
            Forms\Components\Select::make('property_id')
                ->relationship('property', 'name')
                ->label('Propriété')
                ->required(),
            Forms\Components\Textarea::make('review')
                ->label('Avis')
                ->required()
                ->maxLength(65535),
            // Note (1 à 5)
            Forms\Components\Select::make('rating')
                ->label('Note')
                ->options([
                    1 => '1',
                    2 => '2',
                    3 => '3',
                    4 => '4',
                    5 => '5',
                ])
                ->required()
                ->native(false),
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
                    ->label(self::APPROVED_LABEL)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('approved')
                    ->label(self::APPROVED_LABEL)
                    ->query(fn(Builder $query) => $query->where('approved', true)),
                Tables\Filters\Filter::make('pending')
                    ->label('En attente')
                    ->query(fn(Builder $query) => $query->where('approved', false)),
            ])
            ->actions([
                EditAction::make(),
                Action::make('approve')
                    ->label('Approuver')
                    ->action(function (Reviews $record) {
                        $record->update(['approved' => true]);
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn(Reviews $record) => !$record->approved),
                Action::make('disapprove')
                    ->label('Invalider')
                    ->action(function (Reviews $record) {
                        $record->update(['approved' => false]);
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn(Reviews $record) => $record->approved),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
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
    }
}
