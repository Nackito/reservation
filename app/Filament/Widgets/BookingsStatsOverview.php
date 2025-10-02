<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class BookingsStatsOverview extends BaseWidget
{
  protected ?string $pollingInterval = '60s';

  protected function getStats(): array
  {
    $total = Booking::count();

    $hasStatus = Schema::hasColumn('bookings', 'status');
    $hasPaymentStatus = Schema::hasColumn('bookings', 'payment_status');

    $accepted = $hasStatus ? Booking::where('status', 'accepted')->count() : 0;
    $pending = $hasStatus ? Booking::where('status', 'pending')->count() : 0;
    $paid = $hasPaymentStatus ? Booking::where('payment_status', 'paid')->count() : 0;

    return [
      Stat::make('Réservations', (string) $total)
        ->description('Total de réservations')
        ->color('primary')
        ->icon('heroicon-o-home'),

      Stat::make('Acceptées', (string) $accepted)
        ->description('Réservations acceptées')
        ->color('success')
        ->icon('heroicon-o-check-circle'),

      Stat::make('En attente', (string) $pending)
        ->description('En attente de validation')
        ->color('warning')
        ->icon('heroicon-o-clock'),

      Stat::make('Payées', (string) $paid)
        ->description("Paiements confirmés")
        ->color('success')
        ->icon('heroicon-o-banknotes'),
    ];
  }
}
