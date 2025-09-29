<?php

namespace App\Services\Admin;

use App\Models\Booking;
use App\Services\CinetPayService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class BookingActionHelper
{
  private const DATE_FMT = 'd/m/Y';

  public static function handleSimulatePaymentSuccess(Booking $record): void
  {
    try {
      if ($record->payment_status === 'paid') {
        return;
      }
      $txId = $record->payment_transaction_id ?: ('BK-' . $record->id . '-FAKE');
      $record->markAsPaid($txId);
      $conversation = \App\Models\Conversation::where('is_admin_channel', true)
        ->where('booking_id', $record->id)
        ->first();
      if (!$conversation) {
        // même si pas de conversation, on continue pour l'email
      }
      $user = $record->user;
      $amount = self::computeAmount($record);
      $amountFmt = self::formatAmountFrCfa($amount);
      if ($conversation) {
        $msg = "Paiement confirmé (FAKE). Nous avons bien reçu {$amountFmt} FrCFA pour votre réservation. Merci !";
        $message = \App\Models\Message::create([
          'conversation_id' => $conversation->id,
          'sender_id' => Auth::id() ?: 1,
          'receiver_id' => $user ? $user->id : null,
          'content' => $msg,
        ]);
        self::safeBroadcast($message);
      }

      // Emails de confirmation (simulation)
      try {
        if ($user && $user->email) {
          Mail::raw(
            "(FAKE) Votre paiement pour la réservation #{$record->id} a été confirmé. Merci pour votre confiance.",
            function ($m) use ($user, $record) {
              $m->to($user->email)->subject('(FAKE) Paiement confirmé - Réservation #' . $record->id);
            }
          );
        }
        $adminMail = config('mail.admin_email') ?? env('MAIL_ADMIN_EMAIL');
        if ($adminMail) {
          Mail::raw(
            '(FAKE) Paiement confirmé pour la réservation #' . $record->id,
            function ($m) use ($adminMail) {
              $m->to($adminMail)->subject('(FAKE) Paiement confirmé - Réservation');
            }
          );
        }
      } catch (\Throwable $e) {
        Log::warning('Email (FAKE) confirmation paiement non envoyé', ['err' => $e->getMessage()]);
      }
    } catch (\Throwable $e) {
      Log::debug('simulatePaymentSuccess error: ' . $e->getMessage());
    }
  }

  public static function handleAcceptAction(Booking $record): void
  {
    $record->update(['status' => 'accepted']);
    $user = $record->user;
    $admin = Auth::user();
    $amount = self::computeAmount($record);
    $paymentUrl = self::initCinetPayForBooking($record, $amount, $user);
    if ($user) {
      self::notifyUserBookingAccepted($user, $record, $paymentUrl, $amount);
    }
    self::emailAdminAccepted($record, $admin);
    self::sendSystemMessageAccepted($record, $admin, $user, $paymentUrl, $amount);
  }

  public static function handleCancelAction(Booking $record): void
  {
    $record->update(['status' => 'canceled']);
    $user = $record->user;
    $admin = Auth::user();
    if ($user) {
      self::notifyUserBookingCanceled($user, $record);
    }
    self::emailAdminCanceled($record, $admin);
    self::sendSystemMessageCanceled($record, $user);
  }

  public static function handleSimulatePaymentFail(Booking $record): void
  {
    // Met le paiement en échec
    $record->payment_status = 'failed';
    $record->save();

    // Message système dans la conversation liée
    $conversation = \App\Models\Conversation::where('is_admin_channel', true)
      ->where('booking_id', $record->id)
      ->first();
    $user = $record->user;
    if ($conversation) {
      $msg = "Paiement échoué (FAKE). Votre tentative de paiement n'a pas abouti. Vous pouvez réessayer via le lien reçu, ou nous contacter si besoin.";
      $message = \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => 1,
        'receiver_id' => $user ? $user->id : null,
        'content' => $msg,
      ]);
      self::safeBroadcast($message);
    }

    // Email utilisateur
    if ($user && $user->email) {
      try {
        \Illuminate\Support\Facades\Mail::raw(
          "(FAKE) Votre paiement pour la réservation #{$record->id} a échoué. Vous pouvez réessayer via le lien reçu, ou nous contacter si besoin.",
          function ($m) use ($user, $record) {
            $m->to($user->email)->subject('(FAKE) Paiement échoué - Réservation #' . $record->id);
          }
        );
      } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Email echec paiement (fake) non envoyé', ['err' => $e->getMessage()]);
      }
    }
  }

  private static function computeAmount(Booking $record)
  {
    return method_exists($record, 'calculateTotalPrice') ? $record->calculateTotalPrice() : $record->total_price;
  }

  private static function formatAmountFrCfa($amount): string
  {
    return is_numeric($amount) ? number_format($amount, 0, ',', ' ') : (string) $amount;
  }

  private static function initCinetPayForBooking(Booking $record, $amount, $user): string
  {
    $paymentUrl = null;
    try {
      /** @var CinetPayService $cinetpay */
      $cinetpay = app(CinetPayService::class);
      $txId = 'BK-' . $record->id . '-' . time();
      $record->payment_transaction_id = $txId;
      $record->payment_status = 'pending';
      $record->save();
      $desc = 'Paiement réservation #' . $record->id;
      $resp = $cinetpay->initPayment(
        $txId,
        $amount,
        $desc,
        $user?->name,
        $user?->email,
        $user?->phone ?? null
      );
      if (!empty($resp['success'])) {
        $paymentUrl = $resp['url'] ?? null;
      } else {
        Log::warning('CinetPay init échouée', ['resp' => $resp]);
      }
    } catch (\Throwable $e) {
      Log::warning('Erreur CinetPay: ' . $e->getMessage());
    }
    if (!$paymentUrl) {
      $paymentUrl = route('user-reservations');
    }
    return $paymentUrl;
  }

  private static function notifyUserBookingAccepted($user, Booking $record, string $paymentUrl, $amount): void
  {
    try {
      $user->notify(new \App\Notifications\BookingAcceptedNotification($record, $paymentUrl, $amount));
    } catch (\Throwable $e) {
      Log::warning('Notification acceptation non envoyée: ' . $e->getMessage());
    }
    try {
      $mailContent = "Votre réservation a été acceptée, vous pouvez procéder au paiement en cliquant sur le lien ci-dessous.\n\n" .
        "Montant à payer : $amount FrCFA\n" .
        "Lien de paiement : $paymentUrl\n\n" .
        "Sans paiement, nous ne pourrons vous garantir la disponibilité le jour-j.";
      Mail::raw(
        $mailContent,
        function ($message) use ($user) {
          $message->to($user->email)
            ->subject('Votre réservation a été acceptée');
        }
      );
    } catch (\Throwable $e) {
      Log::warning('Email acceptation réservation non envoyé (rate-limit ou SMTP): ' . $e->getMessage());
    }
  }

  private static function emailAdminAccepted(Booking $record, $admin): void
  {
    $adminMail = $admin ? $admin->email : null;
    if (!$adminMail) {
      return;
    }
    $propertyName = $record->property->name ?? '';
    $userName = $record->user ? $record->user->name : '';
    $startDate = $record->start_date;
    $endDate = $record->end_date;
    $createdAt = $record->created_at;
    $adminName = $admin->name ?? '';
    $content = "Réservation acceptée :\n" .
      "- Propriété : $propertyName\n" .
      "- Utilisateur : $userName\n" .
      "- Date d'entrée : $startDate\n" .
      "- Date de sortie : $endDate\n" .
      "- Date de soumission : $createdAt\n" .
      "- Action réalisée par : $adminName";
    try {
      Mail::raw($content, function ($message) use ($adminMail) {
        $message->to($adminMail)
          ->subject('Réservation acceptée - Notification admin');
      });
    } catch (\Throwable $e) {
      Log::warning('Email admin acceptation non envoyé: ' . $e->getMessage());
    }
  }

  private static function sendSystemMessageAccepted(Booking $record, $admin, $user, string $paymentUrl, $amount): void
  {
    $conversation = \App\Models\Conversation::where('is_admin_channel', true)
      ->where('booking_id', $record->id)
      ->first();
    if (!$conversation) {
      return;
    }
    $propertyName = $record->property->name ?? 'votre hébergement';
    $start = $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format(self::DATE_FMT) : '';
    $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date)->format(self::DATE_FMT) : '';
    $amountFmt = self::formatAmountFrCfa($amount);
    $msgContent = "Votre réservation pour {$propertyName} du {$start} au {$end} a été acceptée.\n" .
      "Montant à régler : {$amountFmt} FrCFA.\n" .
      "Veuillez procéder au paiement via ce lien sécurisé :\n{$paymentUrl}\n" .
      "Sans règlement sous 24h, la disponibilité ne peut être garantie.";
    $message = \App\Models\Message::create([
      'conversation_id' => $conversation->id,
      'sender_id' => $admin ? $admin->id : 1,
      'receiver_id' => $user ? $user->id : null,
      'content' => $msgContent,
    ]);
    self::safeBroadcast($message);
  }

  private static function notifyUserBookingCanceled($user, Booking $record): void
  {
    try {
      $user->notify(new \App\Notifications\BookingCanceledNotification($record));
    } catch (\Throwable $e) {
      Log::warning('Notification annulation non envoyée: ' . $e->getMessage());
    }
    try {
      Mail::raw(
        "Votre demande de réservation a été annulée par l'administrateur.",
        function ($message) use ($user) {
          $message->to($user->email)
            ->subject('Votre réservation a été annulée');
        }
      );
    } catch (\Throwable $e) {
      Log::warning('Email annulation utilisateur non envoyé: ' . $e->getMessage());
    }
  }

  private static function emailAdminCanceled(Booking $record, $admin): void
  {
    $adminMail = $admin ? $admin->email : null;
    if (!$adminMail) {
      return;
    }
    $propertyName = $record->property->name ?? '';
    $userName = $record->user ? $record->user->name : '';
    $startDate = $record->start_date;
    $endDate = $record->end_date;
    $createdAt = $record->created_at;
    $adminName = $admin->name ?? '';
    $content = "Réservation annulée :\n" .
      "- Propriété : $propertyName\n" .
      "- Utilisateur : $userName\n" .
      "- Date d'entrée : $startDate\n" .
      "- Date de sortie : $endDate\n" .
      "- Date de soumission : $createdAt\n" .
      "- Action réalisée par : $adminName";
    try {
      Mail::raw($content, function ($message) use ($adminMail) {
        $message->to($adminMail)
          ->subject('Réservation annulée - Notification admin');
      });
    } catch (\Throwable $e) {
      Log::warning('Email annulation admin non envoyé: ' . $e->getMessage());
    }
  }

  private static function sendSystemMessageCanceled(Booking $record, $user): void
  {
    $conversation = \App\Models\Conversation::where('is_admin_channel', true)
      ->where('booking_id', $record->id)
      ->first();
    if (!$conversation) {
      return;
    }
    \App\Models\Message::create([
      'conversation_id' => $conversation->id,
      'sender_id' => 1,
      'receiver_id' => $user ? $user->id : null,
      'content' => "Votre demande de réservation a été annulée par l'administrateur.",
    ]);
  }

  private static function safeBroadcast($message): void
  {
    try {
      broadcast(new \App\Events\MessageSent($message));
    } catch (\Throwable $e) {
      // Ignorer si broadcasting non configuré
    }
  }
}
