<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Message;

class MessageReceivedNotification extends Notification
{
  use Queueable;

  public function __construct(public Message $message) {}

  public function via(object $notifiable): array
  {
    return ['mail'];
  }

  public function toMail(object $notifiable): MailMessage
  {
    $senderName = optional($this->message->sender)->name ?? 'Un utilisateur';
    $preview = str($this->message->content)->limit(120);

    return (new MailMessage)
      ->subject('Nouveau message sur Afridayz')
      ->greeting('Bonjour ' . ($notifiable->name ?? ''))
      ->line($senderName . ' vous a envoyé un nouveau message:')
      ->line('"' . $preview . '"')
      ->action('Ouvrir la messagerie', url('/chat'))
      ->line('À bientôt sur Afridayz.');
  }
}
