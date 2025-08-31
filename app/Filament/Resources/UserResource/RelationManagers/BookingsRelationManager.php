<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Filament\Resources\BookingResource;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BookingsRelationManager extends RelationManager
{
    protected static string $relationship = 'Bookings';

    /*public function form(Form $form): Form
    {
        return $form
            ->schema([
                //    Forms\Components\TextInput::make('id')
                //        ->required()
                //       ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('Booking ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_price')
                    ->label('Price')
                    ->money('EUR'),
                TextColumn::make('property.name')
                    ->label('Property')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable()
                    ->color(fn(string $state) => match ($state) {
                        'pending' => 'info',
                        'accepted' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->icon(fn(string $state) => match ($state) {
                        'pending' => 'heroicon-m-sparkles',
                        'accepted' => 'heroicon-o-check-badge',
                        'cancelled' => 'heroicon-m-x-circle',
                    })
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                //Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Action::make('View Booking')
                    ->url(fn(Booking $record): string => BookingResource::getUrl('edit', ['record' => $record]))
                    ->color('info')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }*/
}
