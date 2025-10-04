<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use Illuminate\Support\Carbon;
use App\Notifications\ReviewReminderNotification;
use App\Models\Conversation;
use App\Models\Message;

class SendReviewReminders extends Command
{
  protected $signature = 'reviews:remind {--days=0 : Envoyer X jours après la date de sortie}';
  protected $description = "Envoie un e-mail aux utilisateurs ayant une réservation payée après la date de sortie pour demander un avis";

  public function handle(): int
  {
    $days = (int) $this->option('days');
    $today = now()->startOfDay();
    $targetDate = $days > 0 ? $today->copy()->subDays($days) : $today->copy()->subDay();

    // Choisir la colonne de suivi en fonction du décalage
    $column = $days >= 7 ? 'review_reminder_sent_7d_at' : 'review_reminder_sent_at';

    $bookings = Booking::query()
      ->where('payment_status', 'paid')
      ->whereDate('end_date', '<=', $targetDate)
      ->whereNull($column)
      ->with(['user', 'property'])
      ->get();

    $count = 0;
    foreach ($bookings as $booking) {
      $user = $booking->user;
      if (!$user || !$user->email) {
        continue;
      }
      $reviewUrl = route('user-reservations.review', ['booking' => $booking->id]);
      $user->notify(new ReviewReminderNotification($booking, $reviewUrl));
      // Marquer l'envoi pour éviter les doublons
      $booking->{$column} = now();
      $booking->save();

      // Envoyer aussi un message dans la conversation de la réservation
      try {
        $conversation = Conversation::where('is_admin_channel', true)
          ->where('booking_id', $booking->id)
          ->first();
        if ($conversation && $user) {
          $propertyName = optional($booking->property)->name ?? 'votre hébergement';
          $content = "Merci pour votre séjour ! Pouvez-vous nous laisser un avis sur {$propertyName} ?\n{$reviewUrl}";
          $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => 1, // bot/système/admin
            'receiver_id' => $user->id,
            'content' => $content,
          ]);
          try {
            broadcast(new \App\Events\MessageSent($message));
          } catch (\Throwable $e) {
            // Ignorer si broadcasting non configuré
          }
        }
      } catch (\Throwable $e) {
        // silencieux
      }
      $count++;
    }

    $this->info("Notifications d'avis envoyées: {$count}");
    return self::SUCCESS;
  }
}
