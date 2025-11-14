<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\Booking;
use App\Services\CinetPayService;

class VerifyPendingPayments extends Command
{
  protected $signature = 'payments:verify-pending {--max-age-hours=72 : Âge max des réservations à vérifier}';
  protected $description = 'Vérifie les paiements en attente via l’API CinetPay et met à jour les réservations.';

  public function handle(): int
  {
    $maxAgeHours = (int) $this->option('max-age-hours');
    $cutoff = now()->subHours($maxAgeHours > 0 ? $maxAgeHours : 72);

    $query = Booking::query()
      ->whereIn('payment_status', ['pending', 'unpaid', null])
      ->whereNotNull('payment_transaction_id')
      ->where('created_at', '>=', $cutoff)
      ->limit(200);

    $count = 0;
    $updated = 0;
    /** @var CinetPayService $cinetpay */
    $cinetpay = app(CinetPayService::class);

    $query->chunkById(100, function ($bookings) use (&$count, &$updated, $cinetpay) {
      foreach ($bookings as $booking) {
        $count++;
        $txId = (string) $booking->payment_transaction_id;
        try {
          $verify = $cinetpay->verifyPayment($txId);
          if (!empty($verify['success']) && !empty($verify['status'])) {
            $status = strtoupper((string) $verify['status']);
            if (in_array($status, ['ACCEPTED', 'SUCCESS', 'PAID', 'PAYMENT_ACCEPTED', 'CONFIRMED'], true)) {
              if ($booking->payment_status !== 'paid') {
                $booking->markAsPaid($txId);
                try {
                  \App\Services\Admin\BookingActionHelper::handlePaymentConflictsForOthers($booking);
                } catch (\Throwable $e) {
                  Log::warning('Cron paiements: conflit non traité', ['booking_id' => $booking->id, 'err' => $e->getMessage()]);
                }
                $updated++;
                Log::info('Cron paiements: réservation marquée payée', ['booking_id' => $booking->id, 'txId' => $txId]);
              }
            } elseif (in_array($status, ['CANCELLED', 'DENIED', 'FAILED', 'REFUSED'], true)) {
              if ($booking->payment_status !== 'failed') {
                $booking->payment_status = 'failed';
                $booking->save();
                Log::info('Cron paiements: réservation marquée failed', ['booking_id' => $booking->id, 'txId' => $txId]);
              }
            }
          }
        } catch (\Throwable $e) {
          Log::warning('Cron paiements: verify error', ['booking_id' => $booking->id, 'txId' => $txId, 'err' => $e->getMessage()]);
        }
      }
    });

    $this->info("Vérifiés: {$count}, mis à jour: {$updated}");
    return self::SUCCESS;
  }
}
