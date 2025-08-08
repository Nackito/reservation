<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChatBox extends Component
{
  public $newMessage;
  public $users;
  public $selectedUser;
  public $messages;

  public function mount()
  {
    $this->users = User::whereNot("id", Auth::id())->latest()->get();
    $this->selectedUser = $this->users->first();
    $this->loadMessages();
  }

  public function loadMessages()
  {
    $this->messages = Message::query()
      ->where(function ($q) {
        $q->where('sender_id', Auth::id())
          ->where('receiver_id', $this->selectedUser->id);
      })
      ->orWhere(function ($q) {
        $q->where('sender_id', $this->selectedUser->id)
          ->where('receiver_id', Auth::id());
      })
      ->latest()->get();
  }

  public function submit()
  {
    if (!$this->newMessage) return;

    $message = Message::create([
      'sender_id' => Auth::id(),
      'receiver_id' => $this->selectedUser->id,
      'content' => $this->newMessage,
    ]);

    $this->messages->push($message);

    $this->newMessage = '';
  }

  public function selectUser($id)
  {
    $this->selectedUser = User::find($id);
    $this->loadMessages();
  }
}
