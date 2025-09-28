<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{receiver_id}', function ($user, $receiverId) {
    return (int) $user->id === (int) $receiverId;
});

// Autoriser le canal de notifications temps réel de Filament
Broadcast::channel('filament.notifications.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
