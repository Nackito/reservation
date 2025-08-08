<?php

namespace App\Livewire;

use App\Filament\Pages\Chat;
use Livewire\Component;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminChatBox extends Component
{
  public $users;
  //public $selectedUserId;
  public $selectedUser;
  public $newMessage;
  public $messages;

  //protected $rules = [
  //'newMessage' => 'required|string',
  //];

  public function mount()
  {
    // Charger tous les utilisateurs sauf l'admin
    $this->users = User::whereNot("id", Auth::id())->latest()->get();
    $this->selectedUser = $this->users->first();
    $this->loadMessages();

    //$first = $this->users->first();
    //$this->selectedUserId = $first?->id;
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

  public function selectUser($id)
  {
    $this->selectedUser = User::find($id);
    $this->loadMessages();
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

  public function updatedSelectedUserId()
  {
    // Réinitialiser le message lors du changement d'utilisateur
    //$this->newMessage = '';
  }

  /*public function sendMessage()
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
    $userId = $this->selectedUserId;
    return Message::where(function ($q) use ($adminId, $userId) {
      $q->where('sender_id', $adminId)
        ->where('receiver_id', $userId);
    })->orWhere(function ($q) use ($adminId, $userId) {
      $q->where('sender_id', $userId)
        ->where('receiver_id', $adminId);
    })->orderBy('created_at', 'asc')->get();
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
  }*/
}
