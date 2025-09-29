<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CinetPayService
{
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
  public function initPayment(string $transactionId, $amount, string $description, ?string $customerName = null, ?string $customerEmail = null, ?string $customerPhone = null): array
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

      $payload = [
        'apikey' => $apiKey,
        'site_id' => $siteId,
        'transaction_id' => $transactionId,
        'amount' => (int) round($amount),
        'currency' => $currency,
        'description' => $description,
        'return_url' => $returnUrl,
        'notify_url' => $notifyUrl,
        'lang' => 'fr',
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone_number' => $customerPhone,
      ];
      if (!empty($channels) && strtoupper($channels) !== 'ALL') {
        $payload['channels'] = $channels;
      }
      // CinetPay exige que `metadata` soit une chaîne (et non un tableau)
      // On envoie une chaîne JSON compacte avec quelques infos utiles
      $payload['metadata'] = json_encode([
        'app' => config('app.name', 'Afridayz'),
        'tx' => $transactionId,
      ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

      try {
        $resp = Http::asJson()->post($initUrl, $payload);
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
      $resp = Http::asJson()->post($checkUrl, $payload);
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
