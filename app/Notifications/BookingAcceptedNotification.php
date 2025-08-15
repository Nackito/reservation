<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Booking;

class BookingAcceptedNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public Booking $booking;

  public function __construct(Booking $booking)
  {
    $this->booking = $booking;
  }

  public function via($notifiable)
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->subject('Votre réservation a été acceptée')
      ->line("Votre réservation à été accepté, vous pouvez procedé au paiement.")
      ->line("Sans paiement, nous ne pourront vous garantir la disponibilité le jour-j");
  }

  public function toArray($notifiable)
  {
    return [
      'booking_id' => $this->booking->id,
      'message' => "Votre réservation à été accepté, vous pouvez procedé au paiement.\nSans paiement, nous ne pourront vous garantir la disponibilité le jour-j",
    ];
  }
}
