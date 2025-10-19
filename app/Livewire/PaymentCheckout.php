<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\CinetPayService;

class PaymentCheckout extends Component
{
  public Booking $booking;
  public $amount;

  public function mount(Booking $booking)
  {
    // Autorisation: seul le propriétaire de la réservation (ou un admin) peut accéder
    $user = Auth::user();
    if (!$user) {
      abort(403);
    }
    // Autoriser uniquement le propriétaire de la réservation
    if ((int) $booking->user_id !== (int) $user->id) {
      abort(403);
    }
    $this->booking = $booking->load(['property.images', 'roomType']);
    $this->amount = method_exists($booking, 'calculateTotalPrice') ? $booking->calculateTotalPrice() : $booking->total_price;
  }

  public function payWithCinetPay()
  {
    try {
      /** @var CinetPayService $cinetpay */
      $cinetpay = app(CinetPayService::class);
      // Générer/attribuer une transaction
      $txId = 'BK-' . $this->booking->id . '-' . time();
      $this->booking->payment_transaction_id = $txId;
      $this->booking->payment_status = 'pending';
      $this->booking->save();
      $desc = 'Paiement réservation #' . $this->booking->id;
      $resp = $cinetpay->initPayment(
        $txId,
        $this->amount,
        $desc,
        $this->booking->user?->name,
        $this->booking->user?->email,
        $this->booking->user?->phone ?? null
      );
      if (!empty($resp['success']) && !empty($resp['url'])) {
        return redirect()->away($resp['url']);
      }
      session()->flash('error', 'Le service de paiement est indisponible pour le moment.');
      return null;
    } catch (\Throwable $e) {
      Log::warning('Erreur init CinetPay (checkout): ' . $e->getMessage());
      session()->flash('error', 'Une erreur est survenue lors de l’initialisation du paiement.');
      return null;
    }
  }

  public function render()
  {
    return view('livewire.payment-checkout');
  }
}
