<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Widgets\BookingStats;
use App\Models\Booking;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    // protected function getHeaderWidgets(): array
    // {
    //     return [
    //         BookingStats::class
    //     ];
    // }

    // protected function getFooterWidgets(): array
    // {
    //     return [
    //         BookingStats::class
    //     ];
    // }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'pending' => Tab::make('En attente')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'pending')),
            'accepted' => Tab::make('Acceptées')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'accepted')),
            'canceled' => Tab::make('Annulées')
                ->modifyQueryUsing(fn($query) => $query->where('status', 'canceled')),
        ];
    }
}
