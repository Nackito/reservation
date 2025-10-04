<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Booking;

class ReviewReminderNotification extends Notification implements ShouldQueue
{
  use Queueable;

  public function __construct(
    public Booking $booking,
    public string $reviewUrl
  ) {}

  public function via(object $notifiable): array
  {
    // mail + base de données (pour affichage dans le site si besoin)
    return ['mail', 'database'];
  }

  public function toMail(object $notifiable): MailMessage
  {
    $propertyName = optional($this->booking->property)->name ?? 'votre hébergement';
    return (new MailMessage)
      ->subject('Donnez votre avis sur ' . $propertyName)
      ->greeting('Bonjour ' . ($notifiable->firstname ?: ($notifiable->name ?: '')))
      ->line("Merci d'avoir séjourné chez nous. Votre avis compte beaucoup !")
      ->line('Cliquez sur le bouton ci-dessous pour laisser un avis sur ' . $propertyName . ' .')
      ->action('Laisser un avis', $this->reviewUrl)
      ->line('Merci pour votre confiance.');
  }

  public function toArray(object $notifiable): array
  {
    return [
      'booking_id' => $this->booking->id,
      'property_id' => $this->booking->property_id,
      'review_url' => $this->reviewUrl,
    ];
  }
}
