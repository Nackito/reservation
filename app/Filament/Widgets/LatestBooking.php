<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\BookingResource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestBooking extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                BookingResource::getEloquentQuery()
            )
            ->defaultPaginationPageOption(5)
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Booking ID')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
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
                    ->color(fn($state) => match ($state) {
                        'pending' => 'info',
                        'accepted' => 'success',
                        'cancelled', 'canceled' => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn($state) => match ($state) {
                        'pending' => 'heroicon-m-sparkles',
                        'accepted' => 'heroicon-o-check-badge',
                        'cancelled', 'canceled' => 'heroicon-m-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->searchable()
                    ->sortable(),
            ]);
    }
}
