<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CinetPayService;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Services\Admin\BookingActionHelper;

class CinetPayController extends Controller
{
  private const NOT_FOUND_MSG = 'Réservation introuvable';
  // Helpers privés
  private function extractTxId(array $payload): ?string
  {
    return data_get($payload, 'transaction_id')
      ?? data_get($payload, 'data.transaction_id')
      ?? data_get($payload, 'cpm_trans_id');
  }

  private function findBookingByTxId(?string $txId): ?Booking
  {
    if (!$txId) {
      return null;
    }
    $booking = Booking::where('payment_transaction_id', $txId)->first();
    if (!$booking && Str::startsWith($txId, 'BK-') && preg_match('/^BK-(\d+)-/', $txId, $m)) {
      $booking = Booking::find((int) $m[1]);
    }
    return $booking;
  }

  private function verifySignature(?string $providedSignature, string $rawBody, ?string $secret): ?bool
  {
    if (!$providedSignature || !$secret) {
      return null;
    }
    $computed = hash_hmac('sha256', $rawBody, $secret);
    return hash_equals($computed, $providedSignature);
  }

  private function auditPayment(?Booking $booking, string $txId, ?string $status, string $source, ?bool $signatureValid, array $context): void
  {
    try {
      Payment::create([
        'booking_id' => $booking?->id,
        'transaction_id' => $txId,
        'status' => $status,
        'source' => $source,
        'signature_valid' => $signatureValid,
        'payload' => $context['payload'] ?? null,
        'headers' => $context['headers'] ?? null,
        'ip' => $context['ip'] ?? null,
      ]);
    } catch (\Throwable $e) {
      Log::warning('Payment audit insert failed', ['err' => $e->getMessage(), 'source' => $source]);
    }
  }

  private function sendPaymentMessageToConversation(Booking $booking, string $msg): void
  {
    $conversation = \App\Models\Conversation::where('is_admin_channel', true)
      ->where('booking_id', $booking->id)
      ->first();
    $user = $booking->user;
    if ($conversation) {
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

  public function notify(Request $request)
  {
    // CinetPay envoie un POST avec les informations de transaction
    $payload = $request->all();
    $headers = [
      'content-type' => $request->header('content-type'),
      'x-cinetpay-signature' => $request->header('x-cinetpay-signature'),
      'user-agent' => $request->header('user-agent'),
    ];
    Log::info('CinetPay notify payload', ['payload' => $payload, 'headers' => $headers]);

    $response = ['status' => 'ok'];
    $httpStatus = 200;

    // Récupérer le transaction_id
    $txId = $this->extractTxId($payload); // compat anciennes refs
    $booking = null;

    if (!$txId) {
      $response = ['status' => 'ignored', 'reason' => 'no transaction_id'];
      $httpStatus = 400;
    } else {
      // Retrouver la réservation par transaction_id, sinon par parsing BK-<id>-
      $booking = $this->findBookingByTxId($txId);
      if (!$booking) {
        Log::warning('CinetPay notify: booking introuvable pour txId', ['txId' => $txId]);
        $response = ['status' => 'ignored', 'reason' => 'booking not found'];
        $httpStatus = 404;
      } else {
        // Vérifier signature et auditer
        $providedSignature = $request->header('x-cinetpay-signature');
        $secret = config('cinetpay.secret_key');
        $signatureValid = $this->verifySignature($providedSignature, $request->getContent(), $secret);
        if ($providedSignature && $secret && $signatureValid === false) {
          $this->auditPayment($booking, $txId, data_get($payload, 'status') ?? data_get($payload, 'data.status'), 'notify', false, [
            'payload' => $payload,
            'headers' => $headers,
            'ip' => $request->ip(),
          ]);
          $response = ['status' => 'invalid-signature'];
          $httpStatus = 401;
        } else {
          $this->auditPayment($booking, $txId, data_get($payload, 'status') ?? data_get($payload, 'data.status'), 'notify', $signatureValid, [
            'payload' => $payload,
            'headers' => $headers,
            'ip' => $request->ip(),
          ]);

          // Vérifier l'état auprès de CinetPay
          /** @var CinetPayService $cinetpay */
          $cinetpay = app(CinetPayService::class);
          $verify = $cinetpay->verifyPayment($txId);
          if (empty($verify['success'])) {
            Log::warning('CinetPay verify échouée', ['txId' => $txId, 'resp' => $verify]);
            $response = ['status' => 'ignored', 'reason' => 'verify failed'];
          } else {
            $status = strtoupper((string)($verify['status'] ?? ''));
            $acceptedStatuses = ['ACCEPTED', 'SUCCESS', 'PAID', 'PAYMENT_ACCEPTED', 'CONFIRMED'];
            $failedStatuses = ['REFUSED', 'CANCELED', 'FAILED', 'DECLINED'];

            if (in_array($status, $acceptedStatuses, true)) {
              if ($booking->payment_status !== 'paid') {
                $booking->markAsPaid($txId);
                // Après paiement confirmé, annuler et notifier les autres réservations en conflit
                try {
                  \App\Services\Admin\BookingActionHelper::handlePaymentConflictsForOthers($booking);
                } catch (\Throwable $e) {
                  Log::warning('Conflit de paiements (notify) non traité', ['err' => $e->getMessage()]);
                }
                $amountFmt = is_numeric($booking->total_price) ? number_format($booking->total_price, 0, ',', ' ') : (string)$booking->total_price;
                $msg = "Paiement confirmé. Nous avons bien reçu {$amountFmt} FrCFA pour votre réservation. Réf: {$txId}. Merci !";
                $this->sendPaymentMessageToConversation($booking, $msg);

                // Emails d'information
                try {
                  $user = $booking->user;
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
            } elseif (in_array($status, $failedStatuses, true)) {
              $booking->payment_status = 'failed';
              $booking->save();
              // Prévenir l'utilisateur de l'échec
              try {
                $user = $booking->user;
                if ($user && $user->email) {
                  Mail::raw(
                    "Votre paiement pour la réservation #{$booking->id} a échoué. Vous pouvez réessayer via le lien de paiement reçu, ou nous contacter si besoin.",
                    function ($m) use ($user, $booking) {
                      $m->to($user->email)->subject('Paiement échoué - Réservation #' . $booking->id);
                    }
                  );
                }
              } catch (\Throwable $e) {
                Log::warning('Email echec paiement non envoyé', ['err' => $e->getMessage()]);
              }
            } else {
              $response = ['status' => 'ok', 'note' => 'status pending'];
            }
          }
        }
      }
    }

    return response()->json($response, $httpStatus);
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
      $booking = $this->findBookingByTxId($txId);
      if ($booking && in_array($status, $accepted, true)) {
        if ($booking->payment_status !== 'paid') {
          // Passage à paid ici: envoyer aussi le message et les emails
          $booking->markAsPaid($txId);
          // Après paiement confirmé, annuler et notifier les autres réservations en conflit
          try {
            \App\Services\Admin\BookingActionHelper::handlePaymentConflictsForOthers($booking);
          } catch (\Throwable $e) {
            Log::warning('Conflit de paiements (return) non traité', ['err' => $e->getMessage()]);
          }
          $amountFmt = is_numeric($booking->total_price) ? number_format($booking->total_price, 0, ',', ' ') : (string)$booking->total_price;
          $msg = "Paiement confirmé. Nous avons bien reçu {$amountFmt} FrCFA pour votre réservation. Réf: {$txId}. Merci !";
          $this->sendPaymentMessageToConversation($booking, $msg);

          try {
            $user = $booking->user;
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
            Log::warning('Email post-paiement (return) non envoyé', ['err' => $e->getMessage()]);
          }
        }
        $statusLabel = 'Paiement confirmé.';
        // Journaliser le retour
        $this->auditPayment($booking, $txId, $status, 'return', null, [
          'payload' => ['query' => $request->query()],
          'headers' => ['user-agent' => $request->header('user-agent')],
          'ip' => $request->ip(),
        ]);
      } elseif ($status) {
        $statusLabel = 'Statut de paiement: ' . $status;
      }
    }
    return redirect()->route('user-reservations')->with('status', $statusLabel);
  }

  // --- Simulation de paiement (pour tests et démo) ---
  public function simulateSuccess(Request $request)
  {
    if (!config('cinetpay.simulation_enabled')) {
      abort(404);
    }
    $txId = $request->input('transaction_id');
    $bookingId = $request->input('booking_id');
    $booking = $this->findBookingByTxId($txId);
    if (!$booking && $bookingId) {
      $booking = \App\Models\Booking::find((int) $bookingId);
      if ($booking && !$txId) {
        $txId = 'BK-' . $booking->id . '-' . now()->timestamp;
      }
    }
    if (!$booking) {
      return response()->json(['ok' => false, 'message' => self::NOT_FOUND_MSG], 404);
    }
    if ($booking->payment_status !== 'paid') {
      $booking->markAsPaid($txId ?? ('BK-' . $booking->id . '-' . now()->timestamp));
      try {
        \App\Services\Admin\BookingActionHelper::handlePaymentConflictsForOthers($booking);
      } catch (\Throwable $e) { /* ignore */
      }
      $amountFmt = is_numeric($booking->total_price) ? number_format($booking->total_price, 0, ',', ' ') : (string)$booking->total_price;
      $tx = $booking->payment_transaction_id ?: ($txId ?? '');
      $this->sendPaymentMessageToConversation($booking, "Paiement confirmé (simulation). Nous avons bien reçu {$amountFmt} FrCFA. Réf: {$tx}");
      // Emails de confirmation
      try {
        $user = $booking->user;
        if ($user && $user->email) {
          \Illuminate\Support\Facades\Mail::raw(
            "Votre paiement (simulation) a été confirmé. Montant: {$amountFmt} FrCFA. Référence: {$tx}.",
            function ($m) use ($user, $booking) {
              $m->to($user->email)->subject('Paiement confirmé (simulation) - Réservation #' . $booking->id);
            }
          );
        }
        $adminMail = config('mail.admin_email') ?? env('MAIL_ADMIN_EMAIL');
        if ($adminMail) {
          \Illuminate\Support\Facades\Mail::raw(
            'Paiement simulé confirmé pour la réservation #' . $booking->id . ' (tx: ' . ($booking->payment_transaction_id ?: '') . ')',
            function ($m) use ($adminMail) {
              $m->to($adminMail)->subject('Paiement confirmé (simulation) - Réservation');
            }
          );
        }
      } catch (\Throwable $e) {
        Log::warning('Email post-paiement simulation non envoyé', ['err' => $e->getMessage()]);
      }
    }
    $this->auditPayment($booking, $txId ?? ('BK-' . $booking->id), 'SIMULATED_SUCCESS', 'simulate', null, [
      'payload' => $request->all(),
      'headers' => ['user-agent' => $request->header('user-agent')],
      'ip' => $request->ip(),
    ]);
    return response()->json(['ok' => true, 'status' => 'paid']);
  }

  public function simulateFail(Request $request)
  {
    if (!config('cinetpay.simulation_enabled')) {
      abort(404);
    }
    $txId = $request->input('transaction_id');
    $bookingId = $request->input('booking_id');
    $booking = $this->findBookingByTxId($txId);
    if (!$booking && $bookingId) {
      $booking = \App\Models\Booking::find((int) $bookingId);
      if ($booking && !$txId) {
        $txId = 'BK-' . $booking->id . '-' . now()->timestamp;
      }
    }
    if (!$booking) {
      return response()->json(['ok' => false, 'message' => self::NOT_FOUND_MSG], 404);
    }
    $booking->payment_status = 'failed';
    $booking->save();
    $this->auditPayment($booking, $txId ?? ('BK-' . $booking->id), 'SIMULATED_FAILED', 'simulate', null, [
      'payload' => $request->all(),
      'headers' => ['user-agent' => $request->header('user-agent')],
      'ip' => $request->ip(),
    ]);
    return response()->json(['ok' => true, 'status' => 'failed']);
  }

  public function simulateCancel(Request $request)
  {
    if (!config('cinetpay.simulation_enabled')) {
      abort(404);
    }
    $txId = $request->input('transaction_id');
    $bookingId = $request->input('booking_id');
    $booking = $this->findBookingByTxId($txId);
    if (!$booking && $bookingId) {
      $booking = \App\Models\Booking::find((int) $bookingId);
      if ($booking && !$txId) {
        $txId = 'BK-' . $booking->id . '-' . now()->timestamp;
      }
    }
    if (!$booking) {
      return response()->json(['ok' => false, 'message' => self::NOT_FOUND_MSG], 404);
    }
    // On ne change pas le statut paid/failed, juste trace l'annulation
    $this->auditPayment($booking, $txId ?? ('BK-' . $booking->id), 'SIMULATED_CANCELED', 'simulate', null, [
      'payload' => $request->all(),
      'headers' => ['user-agent' => $request->header('user-agent')],
      'ip' => $request->ip(),
    ]);
    return response()->json(['ok' => true, 'status' => 'canceled']);
  }
}
