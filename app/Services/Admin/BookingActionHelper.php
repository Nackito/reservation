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


  public static function handleAcceptAction(Booking $record): void
  {
    $record->update(['status' => 'accepted']);
    $user = $record->user;
    $admin = Auth::user();
    $amount = self::computeAmount($record);
    // Ne pas démarrer CinetPay ici: rediriger vers une page de paiement dédiée
    // Mettre le statut de paiement en attente si non défini
    if (empty($record->payment_status) || $record->payment_status !== 'paid') {
      $record->payment_status = 'pending';
      $record->save();
    }
    $paymentUrl = route('payment.checkout', ['booking' => $record->id]);
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


  private static function computeAmount(Booking $record)
  {
    return method_exists($record, 'calculateTotalPrice') ? $record->calculateTotalPrice() : $record->total_price;
  }

  private static function formatAmountFrCfa($amount): string
  {
    return is_numeric($amount) ? number_format($amount, 0, ',', ' ') : (string) $amount;
  }

  // initCinetPayForBooking supprimé: l'init se fera depuis la page de paiement

  private static function notifyUserBookingAccepted($user, Booking $record, string $paymentUrl, $amount): void
  {
    try {
      $user->notify(new \App\Notifications\BookingAcceptedNotification($record, $paymentUrl, $amount));
    } catch (\Throwable $e) {
      Log::warning('Notification acceptation non envoyée: ' . $e->getMessage());
    }
    try {
      $mailContent = "Votre réservation a été acceptée.\n\n" .
        "Montant à payer : $amount FrCFA\n" .
        "Accédez à votre page de paiement : $paymentUrl\n\n" .
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
      "Pour régler, utilisez le bouton Paiement dans ce fil de discussion, ou rendez-vous sur votre page de paiement depuis vos réservations.\n" .
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

  /**
   * Lorsqu'une réservation est payée, prévenir les autres utilisateurs ayant
   * une demande acceptée mais en attente de paiement sur des dates qui se chevauchent
   * pour la même propriété. On annule ces réservations et on envoie message + email.
   */
  public static function handlePaymentConflictsForOthers(Booking $paidBooking): void
  {
    try {
      $conflicts = self::getPendingConflicts($paidBooking);
      if ($conflicts->isEmpty()) {
        return;
      }
      $start = \Carbon\Carbon::parse($paidBooking->start_date);
      $end = \Carbon\Carbon::parse($paidBooking->end_date);
      $paidStartStr = $start->format(self::DATE_FMT);
      $paidEndStr = $end->format(self::DATE_FMT);
      $propertyName = $paidBooking->property->name ?? 'la résidence';

      foreach ($conflicts as $conflict) {
        self::notifyAndCancelConflict($conflict, $propertyName, $paidStartStr, $paidEndStr);
      }
    } catch (\Throwable $e) {
      Log::warning('handlePaymentConflictsForOthers error', ['err' => $e->getMessage()]);
    }
  }

  private static function getPendingConflicts(Booking $paidBooking)
  {
    $propertyId = $paidBooking->property_id;
    if (!$propertyId || !$paidBooking->start_date || !$paidBooking->end_date) {
      return collect();
    }
    $start = \Carbon\Carbon::parse($paidBooking->start_date);
    $end = \Carbon\Carbon::parse($paidBooking->end_date);
    return Booking::query()
      ->where('property_id', $propertyId)
      ->where('id', '!=', $paidBooking->id)
      ->where('status', 'accepted')
      ->where(function ($q) use ($start, $end) {
        $q->where('start_date', '<', $end)->where('end_date', '>', $start);
      })
      ->where(function ($q) {
        $q->whereNull('payment_status')->orWhere('payment_status', 'pending');
      })
      ->get();
  }

  private static function notifyAndCancelConflict(Booking $conflict, string $propertyName, string $paidStartStr, string $paidEndStr): void
  {
    // Idempotence
    if ($conflict->status === 'canceled') {
      return;
    }
    $conflict->status = 'canceled';
    $conflict->payment_status = 'failed';
    $conflict->save();

    $conversation = \App\Models\Conversation::where('is_admin_channel', true)
      ->where('booking_id', $conflict->id)
      ->first();
    $conflictUser = $conflict->user;
    $msg = "Désolé, c'est trop tard : {$propertyName} a été réservée par un autre utilisateur pour les dates du {$paidStartStr} au {$paidEndStr}. Votre demande est annulée.";
    if ($conversation) {
      $message = \App\Models\Message::create([
        'conversation_id' => $conversation->id,
        'sender_id' => 1,
        'receiver_id' => $conflictUser ? $conflictUser->id : null,
        'content' => $msg,
      ]);
      self::safeBroadcast($message);
    }
    try {
      if ($conflictUser && $conflictUser->email) {
        Mail::raw($msg, function ($m) use ($conflictUser) {
          $m->to($conflictUser->email)->subject('Réservation indisponible - Annulation');
        });
      }
    } catch (\Throwable $e) {
      Log::warning('Email annulation pour conflit non envoyé', ['booking_id' => $conflict->id, 'err' => $e->getMessage()]);
    }
  }
}
