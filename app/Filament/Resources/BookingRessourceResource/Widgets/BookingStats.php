<?php

namespace App\Filament\Resources\BookingRessourceResource\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BookingStats extends BaseWidget
{
    protected function getStats(): array
    {
        $userId = Auth::id();

        return [
            Stat::make('Nouvelle reservation', Booking::query()
                ->whereHas('property', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->where('status', 'pending')
                ->count()),

            Stat::make('Reservation acceptée', Booking::query()
                ->whereHas('property', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->where('status', 'accepted')
                ->count()),

            Stat::make('Reservation annulée', Booking::query()
                ->whereHas('property', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->where('status', 'cancelled')
                ->count()),

            Stat::make('Total revenue', Booking::query()
                ->whereHas('property', function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                })
                ->sum('total_price') . ' EUR'),
        ];
    }
}
