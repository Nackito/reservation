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
  public ?string $paymentUrl;
  public $amount;

  public function __construct(Booking $booking, ?string $paymentUrl = null, $amount = null)
  {
    $this->booking = $booking;
    $this->paymentUrl = $paymentUrl;
    $this->amount = $amount;
  }

  public function via($notifiable)
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable)
  {
    $mail = (new MailMessage)
      ->subject('Votre réservation a été acceptée')
      ->line("Votre réservation a été acceptée.");

    if ($this->amount !== null) {
      $mail->line('Montant à payer : ' . $this->amount . ' FrCFA');
    }
    if (!empty($this->paymentUrl)) {
      $mail->line('Lien de paiement : ' . $this->paymentUrl);
    }
    $mail->line("Sans paiement, nous ne pourrons vous garantir la disponibilité le jour-j.");

    return $mail;
  }

  public function toArray($notifiable)
  {
    return [
      'booking_id' => $this->booking->id,
      'message' => "Votre réservation a été acceptée. Sans paiement, nous ne pourrons vous garantir la disponibilité le jour-j.",
      'payment_url' => $this->paymentUrl,
      'amount' => $this->amount,
    ];
  }
}
