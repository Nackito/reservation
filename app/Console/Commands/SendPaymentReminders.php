<?php

namespace App\Console\Commands;

use App\Models\Booking;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendPaymentReminders extends Command
{
  protected $signature = 'payments:remind {--hours=24}';
  protected $description = 'Envoie des relances aux réservations en attente de paiement depuis N heures';

  public function handle(): int
  {
    $hours = (int) $this->option('hours');
    $cutoff = Carbon::now()->subHours($hours);

    $pending = Booking::where('status', 'accepted')
      ->where('payment_status', 'pending')
      ->where('updated_at', '<=', $cutoff)
      ->get();

    $count = 0;
    foreach ($pending as $booking) {
      $user = $booking->user;
      if (!$user || !$user->email) {
        continue;
      }
      try {
        Mail::raw(
          "Rappel: votre réservation #{$booking->id} est toujours en attente de paiement. Merci de finaliser le règlement afin de garantir la disponibilité.",
          function ($m) use ($user, $booking) {
            $m->to($user->email)->subject('Rappel de paiement - Réservation #' . $booking->id);
          }
        );
        $count++;
      } catch (\Throwable $e) {
        Log::warning('Echec d\'envoi rappel paiement', ['booking' => $booking->id, 'err' => $e->getMessage()]);
      }
    }

    $this->info("Relances envoyées: {$count}");
    return self::SUCCESS;
  }
}
