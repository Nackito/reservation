<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CinetPayService;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CinetPayController extends Controller
{
  public function notify(Request $request)
  {
    // CinetPay envoie un POST avec les informations de transaction
    $payload = $request->all();
    Log::info('CinetPay notify payload', ['payload' => $payload]);

    // Récupérer le transaction_id
    $txId = data_get($payload, 'transaction_id')
      ?? data_get($payload, 'data.transaction_id')
      ?? data_get($payload, 'cpm_trans_id'); // compat anciennes refs

    if (!$txId) {
      return response()->json(['status' => 'ignored', 'reason' => 'no transaction_id'], 400);
    }

    // Retrouver la réservation par transaction_id, sinon par parsing BK-<id>-
    $booking = Booking::where('payment_transaction_id', $txId)->first();
    if (!$booking && Str::startsWith($txId, 'BK-')) {
      if (preg_match('/^BK-(\d+)-/', $txId, $m)) {
        $booking = Booking::find((int)$m[1]);
      }
    }
    if (!$booking) {
      Log::warning('CinetPay notify: booking introuvable pour txId', ['txId' => $txId]);
      return response()->json(['status' => 'ignored', 'reason' => 'booking not found'], 404);
    }

    // Vérifier l'état auprès de CinetPay
    /** @var CinetPayService $cinetpay */
    $cinetpay = app(CinetPayService::class);
    $verify = $cinetpay->verifyPayment($txId);
    if (empty($verify['success'])) {
      Log::warning('CinetPay verify échouée', ['txId' => $txId, 'resp' => $verify]);
      return response()->json(['status' => 'ignored', 'reason' => 'verify failed'], 200);
    }

    $status = strtoupper((string)($verify['status'] ?? ''));
    $acceptedStatuses = ['ACCEPTED', 'SUCCESS', 'PAID', 'PAYMENT_ACCEPTED', 'CONFIRMED'];
    $failedStatuses = ['REFUSED', 'CANCELED', 'FAILED', 'DECLINED'];

    if (in_array($status, $acceptedStatuses, true)) {
      if ($booking->payment_status !== 'paid') {
        $booking->markAsPaid($txId);

        // Message système dans la conversation liée
        $conversation = \App\Models\Conversation::where('is_admin_channel', true)
          ->where('booking_id', $booking->id)
          ->first();
        $user = $booking->user;
        if ($conversation) {
          $amountFmt = is_numeric($booking->total_price) ? number_format($booking->total_price, 0, ',', ' ') : (string)$booking->total_price;
          $msg = "Paiement confirmé. Nous avons bien reçu {$amountFmt} FrCFA pour votre réservation. Merci !";
          $message = \App\Models\Message::create([
            'conversation_id' => $conversation->id,
            'sender_id' => 1,
            'receiver_id' => $user ? $user->id : null,
            'content' => $msg,
          ]);
          if ($user) {
            try {
              broadcast(new \App\Events\MessageSent($message));
            } catch (\Throwable $e) { /* ignore */
            }
          }
        }

        // Emails d'information
        try {
          if ($user && $user->email) {
            Mail::raw(
              'Votre paiement a été reçu. Merci pour votre confiance.',
              function ($m) use ($user) {
                $m->to($user->email)->subject('Paiement confirmé');
              }
            );
          }
          $adminMail = config('mail.admin_email') ?? env('MAIL_ADMIN_EMAIL');
          if ($adminMail) {
            Mail::raw(
              'Paiement confirmé pour la réservation #' . $booking->id,
              function ($m) use ($adminMail) {
                $m->to($adminMail)->subject('Paiement confirmé - Réservation');
              }
            );
          }
        } catch (\Throwable $e) {
          Log::warning('Email post-paiement non envoyé', ['err' => $e->getMessage()]);
        }
      }
      return response()->json(['status' => 'ok']);
    }

    if (in_array($status, $failedStatuses, true)) {
      $booking->payment_status = 'failed';
      $booking->save();
      return response()->json(['status' => 'ok']);
    }

    // Autres statuts: laisser en pending
    return response()->json(['status' => 'ok', 'note' => 'status pending']);
  }

  public function return(Request $request)
  {
    $txId = $request->query('transaction_id') ?? $request->input('transaction_id');
    $statusLabel = 'Paiement CinetPay traité.';
    if ($txId) {
      $booking = null;
      /** @var CinetPayService $cinetpay */
      $cinetpay = app(CinetPayService::class);
      $verify = $cinetpay->verifyPayment($txId);
      $status = strtoupper((string)($verify['status'] ?? ''));
      $accepted = ['ACCEPTED', 'SUCCESS', 'PAID', 'PAYMENT_ACCEPTED', 'CONFIRMED'];

      // Tente de retrouver la réservation et la marquer payée si statut accepté
      $booking = Booking::where('payment_transaction_id', $txId)->first();
      if (!$booking && Str::startsWith($txId, 'BK-')) {
        if (preg_match('/^BK-(\d+)-/', $txId, $m)) {
          $booking = Booking::find((int)$m[1]);
        }
      }
      if ($booking && in_array($status, $accepted, true)) {
        if ($booking->payment_status !== 'paid') {
          $booking->markAsPaid($txId);
        }
        $statusLabel = 'Paiement confirmé.';
      } elseif ($status) {
        $statusLabel = 'Statut de paiement: ' . $status;
      }
    }
    return redirect()->route('user-reservations')->with('status', $statusLabel);
  }

  /**
   * Endpoints de test (LOCAL uniquement) pour simuler un paiement.
   * Ils permettent de vérifier rapidement l'UX côté Filament et Chat sans appeler l'API CinetPay.
   */
  public function fakeSuccess(Request $request, Booking $booking)
  {
    if (!app()->environment('local')) {
      abort(403);
    }
    // Génère un txId factice si absent
    $txId = $booking->payment_transaction_id ?: ('BK-' . $booking->id . '-FAKE');
    if ($booking->payment_status !== 'paid') {
      $booking->markAsPaid($txId);

      // Message système dans la conversation liée (même comportement que notify)
      $conversation = \App\Models\Conversation::where('is_admin_channel', true)
        ->where('booking_id', $booking->id)
        ->first();
      $user = $booking->user;
      if ($conversation) {
        $amountFmt = is_numeric($booking->total_price) ? number_format($booking->total_price, 0, ',', ' ') : (string)$booking->total_price;
        $msg = "Paiement confirmé (FAKE). Nous avons bien reçu {$amountFmt} FrCFA pour votre réservation. Merci !";
        $message = \App\Models\Message::create([
          'conversation_id' => $conversation->id,
          'sender_id' => 1,
          'receiver_id' => $user ? $user->id : null,
          'content' => $msg,
        ]);
        if ($user) {
          try {
            broadcast(new \App\Events\MessageSent($message));
          } catch (\Throwable $e) { /* ignore */
          }
        }
      }
    }

    // Redirige vers la liste des réservations Filament si possible, sinon page précédente
    return back()->with('status', 'Paiement simulé: confirmé.');
  }

  public function fakeFail(Request $request, Booking $booking)
  {
    if (!app()->environment('local')) {
      abort(403);
    }
    $booking->payment_status = 'failed';
    $booking->save();
    return back()->with('status', 'Paiement simulé: échoué.');
  }
}
