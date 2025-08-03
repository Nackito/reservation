<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class Messaging extends Component
{
  public $messages;
  public $newMessage = '';

  public function mount()
  {
    $this->loadMessages();
  }

  public function loadMessages()
  {
    $userId = Auth::id();
    $this->messages = Message::where('receiver_id', $userId)
      ->orWhere('sender_id', $userId)
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function sendMessage($receiverId)
  {
    Message::create([
      'sender_id' => Auth::id(),
      'receiver_id' => $receiverId,
      'content' => $this->newMessage,
    ]);
    $this->newMessage = '';
    $this->loadMessages();
  }

  public function render()
  {
    return view('livewire.messaging');
  }
}
