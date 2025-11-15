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

  /**
   * Calcule les montants/devise pour affichage et pour l'appel API.
   * - Le montant source ($this->amount) est en devise de base (config('cinetpay.currency'), XOF par défaut)
   * - On tente de convertir vers la devise de l'utilisateur pour l'affichage
   * - On utilise une liste blanche de devises autorisées pour l'API CinetPay; sinon, fallback à la devise de base
   * Retour:
   *   [
   *     'amount_for_payment' => float|int,
   *     'currency_for_payment' => string,
   *     'display_amount' => float|int, // ce que l'on montre à l'utilisateur
   *     'display_currency' => string,
   *     'rate' => float|null,
   *   ]
   */
  private function computePayAmountAndCurrency(): array
  {
    $baseCurrency = strtoupper((string) config('cinetpay.currency', 'XOF'));
    $allowed = (array) config('cinetpay.allowed_currencies', [$baseCurrency]);
    $allowed = array_values(array_unique(array_map('strtoupper', array_map('trim', $allowed))));
    $user = $this->booking->user;
    $userCurrency = strtoupper((string) ($user?->currency ?: $baseCurrency));

    $rate = null;
    $displayAmount = $this->amount;
    $displayCurrency = $baseCurrency;

    if ($userCurrency !== $baseCurrency) {
      try {
        /** @var \App\Livewire\BookingManager $bm */
        $bm = app(\App\Livewire\BookingManager::class);
        $rate = $bm->getExchangeRate($baseCurrency, $userCurrency);
        if (is_numeric($rate) && (float) $rate > 0) {
          $displayAmount = (float) $this->amount * (float) $rate;
          $displayCurrency = $userCurrency;
        } else {
          $rate = null;
        }
      } catch (\Throwable $e) {
        $rate = null;
      }
    }

    // Devise réellement utilisée côté API: uniquement si autorisée, sinon devise de base
    $currencyForPayment = in_array($userCurrency, $allowed, true) ? $userCurrency : $baseCurrency;
    $amountForPayment = ($currencyForPayment === $baseCurrency) ? $this->amount : $displayAmount;

    return [
      'amount_for_payment' => $amountForPayment,
      'currency_for_payment' => $currencyForPayment,
      'display_amount' => $displayAmount,
      'display_currency' => $displayCurrency,
      'rate' => $rate !== null ? (float) $rate : null,
    ];
  }

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
      // Déterminer montant/devise à utiliser
      $pay = $this->computePayAmountAndCurrency();
      // Générer/attribuer une transaction
      $txId = 'BK-' . $this->booking->id . '-' . time();
      $this->booking->payment_transaction_id = $txId;
      $this->booking->payment_status = 'pending';
      $this->booking->save();
      // Description sans caractères spéciaux (#,/,$,_,&) selon doc CinetPay
      $desc = 'Paiement reservation ' . $this->booking->id;
      $resp = $cinetpay->initPayment(
        $txId,
        $pay['amount_for_payment'],
        $desc,
        $this->booking->user?->name,
        $this->booking->user?->email,
        $this->booking->user?->phone ?? null,
        [
          'currency' => $pay['currency_for_payment'],
          // Conserver la devise affichée dans les métadonnées pour réconciliation éventuelle
          'display_currency' => $pay['display_currency'],
          'display_amount' => $pay['display_amount'],
        ]
      );
      if (!empty($resp['success']) && !empty($resp['url'])) {
        Log::debug('CinetPay CB init success', [
          'txId' => $txId,
          'booking_id' => $this->booking->id,
          'url' => $resp['url'],
          'currency' => $pay['currency_for_payment'] ?? null,
        ]);
        // Ouvrir dans un nouvel onglet via événement navigateur (géré côté Blade)
        $this->dispatch('open-new-tab', url: $resp['url']);
        return null;
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
      // Déterminer montant/devise à utiliser
      $pay = $this->computePayAmountAndCurrency();
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

      // Forcer l'univers de paiement par carte uniquement pour ce flux
      $overrides = [
        'channels' => 'CREDIT_CARD',
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

      // Ajouter la devise choisie pour l'API + infos d'affichage
      $overrides['currency'] = $pay['currency_for_payment'];
      $overrides['display_currency'] = $pay['display_currency'];
      $overrides['display_amount'] = $pay['display_amount'];
      $resp = $cinetpay->initPayment(
        $txId,
        $pay['amount_for_payment'],
        $desc,
        $name,
        $user?->email,
        $phone,
        $overrides
      );
      if (!empty($resp['success']) && !empty($resp['url'])) {
        // Ouvrir dans un nouvel onglet via événement navigateur (géré côté Blade)
        $this->dispatch('open-new-tab', url: $resp['url']);
        return null;
      }
      Log::warning('CinetPay CB initPayment a échoué', [
        'txId' => $txId,
        'booking_id' => $this->booking->id,
        'error' => $resp['error'] ?? null,
        'api_response' => $resp['response'] ?? null,
        'currency' => $pay['currency_for_payment'] ?? null,
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
