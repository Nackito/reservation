<?php

return [
  // Identifiant de l'utilisateur Admin/support (utilisé pour filtrer les DMs et router certains messages)
  'admin_user_id' => env('CHAT_ADMIN_USER_ID', 5),
  'archive' => [
    // Délai d'archivage des canaux de réservation: nb de jours après la date de sortie (checkout)
    'booking_grace_days' => env('CHAT_ARCHIVE_BOOKING_GRACE_DAYS', 2),
    // Délai d'inactivité pour archiver les discussions directes (en jours)
    'direct_inactive_days' => env('CHAT_ARCHIVE_DIRECT_INACTIVE_DAYS', 14),
  ],
];
