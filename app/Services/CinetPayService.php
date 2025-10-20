<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CinetPayService
{
  /**
   * Construit le payload de base pour l'initialisation CinetPay.
   */
  private function buildInitPayload(string $transactionId, $amount, string $currency, string $description, $urls, $customer): array
  {
    // Montant entier, contrainte CinetPay: multiple de 5 sauf USD
    $amountInt = (int) round($amount);
    if (strtoupper((string) $currency) !== 'USD' && ($amountInt % 5) !== 0) {
      $amountInt = (int) (ceil($amountInt / 5) * 5);
    }
    $urlsReturn = is_array($urls) ? ($urls['return'] ?? null) : null;
    $urlsNotify = is_array($urls) ? ($urls['notify'] ?? null) : null;
    $customerName = is_array($customer) ? ($customer['name'] ?? null) : null;
    $customerEmail = is_array($customer) ? ($customer['email'] ?? null) : null;
    $customerPhone = is_array($customer) ? ($customer['phone'] ?? null) : null;

    return [
      'transaction_id' => $transactionId,
      'amount' => $amountInt,
      'currency' => $currency,
      'alternative_currency' => '',
      'description' => $description,
      'return_url' => $urlsReturn,
      'notify_url' => $urlsNotify,
      'lang' => 'FR',
      'customer_name' => $customerName,
      'customer_email' => $customerEmail,
      'customer_phone_number' => $customerPhone,
    ];
  }

  /**
   * Applique les overrides (channels, infos client CB) au payload.
   */
  private function applyOverrides(array $payload, array $overrides, ?string $defaultChannels): array
  {
    if (!empty($overrides)) {
      if (!empty($overrides['channels'])) {
        $payload['channels'] = $overrides['channels'];
      } elseif (!empty($defaultChannels) && strtoupper($defaultChannels) !== 'ALL') {
        $payload['channels'] = $defaultChannels;
      }
      foreach (
        [
          'customer_id',
          'customer_surname',
          'customer_address',
          'customer_city',
          'customer_country',
          'customer_state',
          'customer_zip_code',
          'lang',
          'invoice_data',
        ] as $k
      ) {
        if (array_key_exists($k, $overrides) && $overrides[$k] !== null) {
          $payload[$k] = $overrides[$k];
        }
      }
    } else {
      if (!empty($defaultChannels) && strtoupper($defaultChannels) !== 'ALL') {
        $payload['channels'] = $defaultChannels;
      }
    }
    return $payload;
  }
  /**
   * Initialise un paiement CinetPay et retourne l'URL de redirection
   *
   * @param string $transactionId Identifiant unique (à générer côté app)
   * @param int|float $amount Montant en devise CinetPay (ex: XOF)
   * @param string $description Description visible côté CinetPay
   * @param string|null $customerName
   * @param string|null $customerEmail
   * @param string|null $customerPhone
   * @return array{success:bool,url?:string,response?:mixed,error?:string}
   */
  public function initPayment(string $transactionId, $amount, string $description, ?string $customerName = null, ?string $customerEmail = null, ?string $customerPhone = null, array $overrides = []): array
  {
    $result = ['success' => false];
    $apiKey = config('cinetpay.api_key');
    $siteId = config('cinetpay.site_id');
    $currency = config('cinetpay.currency', 'XOF');
    $channels = config('cinetpay.channels', 'ALL');
    $initUrl = config('cinetpay.init_url');
    $returnUrl = config('cinetpay.return_url');
    $notifyUrl = config('cinetpay.notify_url');

    if (!$apiKey || !$siteId) {
      $result['error'] = 'CinetPay non configuré (API_KEY/SITE_ID manquants)';
    } else {
      $payload = $this->buildInitPayload(
        $transactionId,
        $amount,
        $currency,
        $description,
        ['return' => $returnUrl, 'notify' => $notifyUrl],
        ['name' => $customerName, 'email' => $customerEmail, 'phone' => $customerPhone]
      );
      // clés obligatoires api + site
      $payload['apikey'] = $apiKey;
      $payload['site_id'] = $siteId;
      // Overrides (channels & infos client étendues pour CB)
      $payload = $this->applyOverrides($payload, $overrides, $channels);
      // CinetPay exige que `metadata` soit une chaîne (et non un tableau)
      // On envoie une chaîne JSON compacte avec quelques infos utiles
      $payload['metadata'] = json_encode([
        'app' => config('app.name', 'Afridayz'),
        'tx' => $transactionId,
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

      try {
        $resp = Http::timeout((int) config('cinetpay.timeout', 8))
          ->connectTimeout((int) config('cinetpay.connect_timeout', 5))
          ->withHeaders(['User-Agent' => config('app.name', 'Afridayz') . '/1.0'])
          ->asJson()
          ->post($initUrl, $payload);
        if (!$resp->successful()) {
          $result['error'] = 'HTTP ' . $resp->status();
          $result['response'] = $resp->json();
        } else {
          $data = $resp->json();
          $url = data_get($data, 'data.payment_url') ?? data_get($data, 'data.payment_link') ?? data_get($data, 'data.checkout_url');
          if (!$url) {
            $result['error'] = 'URL de paiement introuvable';
            $result['response'] = $data;
          } else {
            $result = ['success' => true, 'url' => $url, 'response' => $data];
          }
        }
      } catch (\Throwable $e) {
        $result['error'] = $e->getMessage();
      }
    }

    return $result;
  }

  /**
   * Vérifie l'état d'une transaction
   * @return array{success:bool,status?:string,response?:mixed,error?:string}
   */
  public function verifyPayment(string $transactionId): array
  {
    $result = ['success' => false];
    $apiKey = config('cinetpay.api_key');
    $siteId = config('cinetpay.site_id');
    $checkUrl = config('cinetpay.check_url');
    if (!$apiKey || !$siteId) {
      $result['error'] = 'CinetPay non configuré (API_KEY/SITE_ID manquants)';
      return $result;
    }
    $payload = [
      'apikey' => $apiKey,
      'site_id' => $siteId,
      'transaction_id' => $transactionId,
    ];
    try {
      $resp = Http::asJson()
        ->timeout((int) config('cinetpay.timeout', 8))
        ->connectTimeout((int) config('cinetpay.connect_timeout', 5))
        ->retry(1, 200)
        ->post($checkUrl, $payload);
      $data = $resp->json();
      if (!$resp->successful()) {
        $result['error'] = 'HTTP ' . $resp->status();
        $result['response'] = $data;
      } else {
        // Selon la doc CinetPay, le statut peut se trouver sous data.status ou data.payment_status
        $status = data_get($data, 'data.status') ?? data_get($data, 'data.payment_status') ?? null;
        if ($status) {
          $result = ['success' => true, 'status' => (string)$status, 'response' => $data];
        } else {
          $result['error'] = 'Statut introuvable';
          $result['response'] = $data;
        }
      }
    } catch (\Throwable $e) {
      $result['error'] = $e->getMessage();
    }
    return $result;
  }
}
