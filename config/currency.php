<?php

return [
  // Codes devises pris en charge par notre logique (exchangerate.host + pivot EUR)
  'supported' => [
    'XOF', // Franc CFA BCEAO
    'EUR',
    'USD',
    'XAF', // Franc CFA BEAC
    'NGN', // Naira
    'GHS', // Ghana Cedi
  ],

  // Libellés affichés côté UI
  'labels' => [
    'XOF' => 'Franc CFA (XOF)',
    'EUR' => 'Euro (EUR)',
    'USD' => 'US Dollar (USD)',
    'XAF' => 'Franc CFA (XAF)',
    'NGN' => 'Naira (NGN)',
    'GHS' => 'Ghana Cedi (GHS)',
  ],
];
