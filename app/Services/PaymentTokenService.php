<?php

namespace App\Services;

class PaymentTokenService
{
  // Simule une tokenisation PSP. En prod, remplacer par l'appel au SDK du PSP.
  public function tokenizeCard(array $card): array
  {
    // $card: ['number','exp_month','exp_year','cardholder']
    $digits = preg_replace('/\D+/', '', $card['number'] ?? '');
    $last4 = substr($digits, -4);
    // Simuler une marque détectée grossièrement
    $brand = 'card';
    if (preg_match('/^4/', $digits)) $brand = 'visa';
    if (preg_match('/^5[1-5]/', $digits)) $brand = 'mastercard';
    if (preg_match('/^3[47]/', $digits)) $brand = 'amex';

    // Simuler token
    $token = 'tok_' . bin2hex(random_bytes(8));

    return [
      'token' => $token,
      'last4' => $last4,
      'brand' => $brand,
    ];
  }
}
