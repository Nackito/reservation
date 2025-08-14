<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use App\Filament\Resources\BookingRessourceResource\Widgets\BookingStats;
use App\Models\Booking;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
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

    protected function getHeaderWidgets(): array
    {
        return [
            BookingStats::class
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            BookingStats::class
        ];
    }

    protected function getTables(): array
    {
        return [
            null => Tab::make('All'),
            'pending' => Tab::make()->query(fn($query) => $query->where('status', 'pending')),
            'accepted' => Tab::make()->query(fn($query) => $query->where('status', 'accepted')),
            'canceled' => Tab::make()->query(fn($query) => $query->where('status', 'canceled')),
        ];
    }
}
