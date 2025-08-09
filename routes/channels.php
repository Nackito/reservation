<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{receiver_id}', function ($user, $receiverId) {
    return (int) $user->id === (int) $receiverId;
});
