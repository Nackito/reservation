<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
  protected function schedule(Schedule $schedule): void
  {
    // Relance toutes les 24h pour les paiements en attente depuis 24h
    $schedule->command('payments:remind --hours=24')->dailyAt('09:00');
    // Optionnel: relance 48h si toujours en attente
    $schedule->command('payments:remind --hours=48')->dailyAt('09:15');
  }

  protected function commands(): void
  {
    $this->load(__DIR__ . '/Commands');
  }
}
