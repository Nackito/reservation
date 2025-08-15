<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Booking;

class BookingCanceledNotification extends Notification implements ShouldQueue
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
      ->subject('Votre réservation a été annulée')
      ->greeting('Bonjour ' . ($notifiable->name ?? ''))
      ->line('Votre demande de réservation pour la propriété "' . ($this->booking->property->name ?? '') . '" a été annulée.')
      ->line('Date de début : ' . $this->booking->start_date)
      ->line('Date de fin : ' . $this->booking->end_date)
      ->line('Montant : ' . $this->booking->total_price . ' FrCFA')
      ->line('Statut : Annulée')
      ->action('Voir mes réservations', url('/user-reservations'))
      ->line('Pour toute question, contactez notre service client.');
  }

  public function toDatabase($notifiable)
  {
    return [
      'booking_id' => $this->booking->id,
      'property_name' => $this->booking->property->name ?? '',
      'start_date' => $this->booking->start_date,
      'end_date' => $this->booking->end_date,
      'total_price' => $this->booking->total_price,
      'status' => 'canceled',
      'message' => 'Votre demande de réservation a été annulée.'
    ];
  }
}
