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
    $adminId = Auth::id();
    return Message::where('receiver_id', $adminId)
      ->orderBy('created_at', 'desc')
      ->get();
  }

  public function getConversationsProperty()
  {
    return Message::select('sender_id')
      ->distinct()
      ->with('sender')
      ->where('receiver_id', Auth::id())
      ->get();
  }

  public function render()
  {
    return view('livewire.admin-chat-box', [
      'conversations' => $this->conversations,
      'messages' => $this->messages,
    ]);
  }
}
