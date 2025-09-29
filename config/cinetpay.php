<?php

return [
  'api_key' => env('CINETPAY_API_KEY'),
  'site_id' => env('CINETPAY_SITE_ID'),
  'secret_key' => env('CINETPAY_SECRET_KEY'),
  'currency' => env('CINETPAY_CURRENCY', 'XOF'),
  'channels' => env('CINETPAY_CHANNELS', 'ALL'),
  // API base URL for initialization
  'init_url' => env('CINETPAY_INIT_URL', 'https://api-checkout.cinetpay.com/v2/payment'),
  // API URL for verify/check transaction
  'check_url' => env('CINETPAY_CHECK_URL', 'https://api-checkout.cinetpay.com/v2/payment/check'),
  // Callback URLs
  'notify_url' => env('CINETPAY_NOTIFY_URL', rtrim(env('APP_URL', ''), '/') . '/cinetpay/notify'),
  'return_url' => env('CINETPAY_RETURN_URL', rtrim(env('APP_URL', ''), '/') . '/cinetpay/return'),
];
