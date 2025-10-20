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
      // Description sans caractères spéciaux (#,/,$,_,&) selon doc CinetPay
      $desc = 'Paiement reservation ' . $this->booking->id;
      $resp = $cinetpay->initPayment(
        $txId,
        $this->amount,
        $desc,
        $this->booking->user?->name,
        $this->booking->user?->email,
        $this->booking->user?->phone ?? null
      );
      if (!empty($resp['success']) && !empty($resp['url'])) {
        Log::debug('CinetPay CB init success', [
          'txId' => $txId,
          'booking_id' => $this->booking->id,
          'url' => $resp['url'],
        ]);
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

  public function payWithCinetPayCard()
  {
    try {
      /** @var CinetPayService $cinetpay */
      $cinetpay = app(CinetPayService::class);
      $txId = 'BK-' . $this->booking->id . '-' . time();
      $this->booking->payment_transaction_id = $txId;
      $this->booking->payment_status = 'pending';
      $this->booking->save();
      // Description sans caractères spéciaux (#,/,$,_,&) selon doc CinetPay
      $desc = 'Paiement carte reservation ' . $this->booking->id;
      $user = $this->booking->user;
      // Normalisation des identités et coordonnées (valeurs par défaut sûres)
      $name = trim((string) ($user?->name ?? 'Client'));
      $firstname = trim((string) ($user?->firstname ?? ''));
      $accountCountry = (string) strtoupper(config('cinetpay.account_country', 'CI'));
      $city = (string) ($user?->city ?? ($this->booking->property->city ?? 'Abidjan'));
      $address = (string) ($user?->address ?? 'Adresse non fournie');
      $zip = (string) ($user?->zip_code ?? '00000');
      $phone = (string) ($user?->phone ?? '+225000000000');
      // Fallback simple si firstname vide
      if ($firstname === '') {
        $firstname = 'Client';
      }
      // Ensure we send a valid ISO-2 uppercase country code to CinetPay.
      $rawCountry = (string) ($user?->country_code ?? $accountCountry);
      // If the stored value looks like a phone prefix (+225 or 00225 or digits), fallback to account country.
      if (preg_match('/^[+0-9]/', $rawCountry)) {
        $iso2 = $accountCountry;
      } else {
        // Try to normalize: take first two letters and uppercase (common case: 'CI', 'FR')
        $iso2 = strtoupper(substr($rawCountry, 0, 2));
      }

      $forceCard = (bool) config('cinetpay.force_card', false);
      $overrides = [
        'channels' => $forceCard ? 'CREDIT_CARD' : 'ALL',
        // Champs client recommandés pour CB (mettre des défauts si non disponibles)
        'customer_id' => (string) ($user?->id ?? $this->booking->user_id),
        'customer_surname' => $firstname,
        'customer_address' => $address,
        'customer_city' => $city,
        'customer_country' => $iso2,
        'customer_state' => $iso2,
        'customer_zip_code' => substr(preg_replace('/\D/', '', $zip) ?: '00000', 0, 5),
        'lang' => 'FR',
      ];
      // Log non-sensitive override fields for diagnostics (no card data, no secret info)
      Log::debug('CinetPay CB init payload overrides', [
        'txId' => $txId,
        'booking_id' => $this->booking->id,
        'overrides' => collect($overrides)->only(['channels', 'customer_country', 'customer_state', 'customer_city', 'customer_zip_code', 'customer_address'])->toArray(),
      ]);

      $resp = $cinetpay->initPayment(
        $txId,
        $this->amount,
        $desc,
        $name,
        $user?->email,
        $phone,
        $overrides
      );
      if (!empty($resp['success']) && !empty($resp['url'])) {
        return redirect()->away($resp['url']);
      }
      Log::warning('CinetPay CB initPayment a échoué', [
        'txId' => $txId,
        'booking_id' => $this->booking->id,
        'error' => $resp['error'] ?? null,
        'api_response' => $resp['response'] ?? null,
      ]);
      $apiMsg = (string) (data_get($resp, 'response.message') ?? data_get($resp, 'response.description') ?? $resp['error'] ?? '');
      session()->flash('error', 'Impossible d’ouvrir le guichet CB pour le moment.' . ($apiMsg ? ' Détail: ' . $apiMsg : ''));
      return null;
    } catch (\Throwable $e) {
      Log::warning('Erreur init CinetPay CB (checkout): ' . $e->getMessage());
      session()->flash('error', 'Une erreur est survenue lors de l’initialisation du paiement par carte.');
      return null;
    }
  }

  public function render()
  {
    return view('livewire.payment-checkout')->layout('layouts.app');
  }
}
