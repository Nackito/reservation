<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use App\Models\Booking;

class BookingRequestNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public Booking $booking;

  public function __construct(Booking $booking)
  {
    $this->booking = $booking;
  }

  public function via($notifiable)
  {
    return ['database'];
  }

  public function toDatabase($notifiable)
  {
    return [
      'booking_id' => $this->booking->id,
      'user_id' => $this->booking->user_id,
      'property_id' => $this->booking->property_id,
      'message' => 'Nouvelle demande de réservation reçue.',
    ];
  }
}
