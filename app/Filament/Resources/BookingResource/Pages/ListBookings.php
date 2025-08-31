<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingResource\Widgets\BookingStats;
use App\Models\Booking;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\Tab;
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
            'all' => [
                'label' => 'Tous',
            ],
            'pending' => [
                'label' => 'En attente',
                'query' => fn($query) => $query->where('status', 'pending'),
            ],
            'accepted' => [
                'label' => 'Acceptées',
                'query' => fn($query) => $query->where('status', 'accepted'),
            ],
            'canceled' => [
                'label' => 'Annulées',
                'query' => fn($query) => $query->where('status', 'canceled'),
            ],
        ];
    }
}
