<?php

return [
  'api_key' => env('CINETPAY_API_KEY'),
  'site_id' => env('CINETPAY_SITE_ID'),
  'secret_key' => env('CINETPAY_SECRET_KEY'),
  'currency' => env('CINETPAY_CURRENCY', 'XOF'),
  // Liste blanche des devises autorisées par le compte marchand.
  // Par défaut, on restreint à la devise principale du compte.
  // Pour en autoriser plusieurs: CINETPAY_ALLOWED_CURRENCIES="XOF,USD,EUR"
  'allowed_currencies' => array_values(array_filter(array_map(
    'strtoupper',
    array_map('trim', explode(',', env('CINETPAY_ALLOWED_CURRENCIES', env('CINETPAY_CURRENCY', 'XOF'))))
  ))),
  'channels' => env('CINETPAY_CHANNELS', 'ALL'),
  // Forcer le canal CB uniquement depuis l'env, sinon ALL (évite écran vide si CB non activé)
  'force_card' => env('CINETPAY_FORCE_CREDIT_CARD', false),
  // Activer les routes de simulation (désactivé par défaut en production)
  'simulation_enabled' => env('CINETPAY_SIMULATION', env('APP_ENV') !== 'production'),
  // API base URL for initialization
  'init_url' => env('CINETPAY_INIT_URL', 'https://api-checkout.cinetpay.com/v2/payment'),
  // API URL for verify/check transaction
  'check_url' => env('CINETPAY_CHECK_URL', 'https://api-checkout.cinetpay.com/v2/payment/check'),
  // Callback URLs
  'notify_url' => env('CINETPAY_NOTIFY_URL', rtrim(env('APP_URL', ''), '/') . '/cinetpay/notify'),
  'return_url' => env('CINETPAY_RETURN_URL', rtrim(env('APP_URL', ''), '/') . '/cinetpay/return'),
  // Pays ISO2 de votre compte CinetPay (utilisé pour l'univers CB)
  'account_country' =>  env('CINETPAY_ACCOUNT_COUNTRY', 'CI'),
  // Nombre de tentatives de vérification côté return_url (poll API) avant d'abandonner l'affichage du statut.
  'return_verify_attempts' => (int) env('CINETPAY_RETURN_VERIFY_ATTEMPTS', 3),
  // Délai (ms) entre deux tentatives de vérification sur return_url
  'return_verify_delay_ms' => (int) env('CINETPAY_RETURN_VERIFY_DELAY_MS', 600),
];
