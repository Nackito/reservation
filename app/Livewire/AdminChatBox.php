<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminChatBox extends Component
{
  public $users;
  public $selectedUserId;
  public $newMessage = '';

  protected $rules = [
    'newMessage' => 'required|string',
  ];

  public function mount()
  {
    // Charger tous les utilisateurs sauf l'admin
    $this->users = User::where('email', '!=', Auth::user()->email)->get();
    $first = $this->users->first();
    $this->selectedUserId = $first?->id;
  }

  public function updatedSelectedUserId()
  {
    // Réinitialiser le message lors du changement d'utilisateur
    $this->newMessage = '';
  }

  public function sendMessage()
  {
    $this->validate();

    Message::create([
      'sender_id' => Auth::id(),
      'receiver_id' => $this->selectedUserId,
      'content' => $this->newMessage,
    ]);

    $this->newMessage = '';
  }

  public function getMessagesProperty()
  {
    $userId = Auth::id();

    return Message::where(function ($q) use ($userId) {
      $q->where('sender_id', $userId)
        ->where('receiver_id', $this->selectedUserId);
    })->orWhere(function ($q) use ($userId) {
      $q->where('sender_id', $this->selectedUserId)
        ->where('receiver_id', $userId);
    })->orderBy('created_at')->get();
  }

  public function render()
  {
    return view('livewire.admin-chat-box', [
      'messages' => $this->messages,
    ]);
  }
}
