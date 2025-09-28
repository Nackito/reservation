<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public function __construct(
    public int $sender_id,
    public int $receiver_id,
    public string $sender_name
  ) {}

  public function broadcastOn(): array
  {
    return [new PrivateChannel('chat.' . $this->receiver_id)];
  }

  public function broadcastWith(): array
  {
    return [
      'userID' => $this->sender_id,
      'userName' => $this->sender_name,
    ];
  }

  public function broadcastAs(): string
  {
    return 'UserTyping';
  }
}
