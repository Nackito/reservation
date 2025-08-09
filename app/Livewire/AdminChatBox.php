<?php

namespace App\Livewire;

use App\Filament\Pages\Chat;
use Livewire\Component;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;

class AdminChatBox extends Component
{
  public $users;
  public $selectedUser;
  public $newMessage;
  public $messages;
  public $loginID;

  public function mount()
  {
    // Charger tous les utilisateurs sauf l'admin
    $this->users = User::whereNot("id", Auth::id())->latest()->get();
    $this->selectedUser = $this->users->first();
    $this->loadMessages();
    $this->loginID = Auth::id();

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
      })->get();
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

    broadcast(new MessageSent($message));
  }

  public function getListeners()
  {
    return [
      "echo-private:chat.{$this->loginID},MessageSent" => "newChatMessageNotification"
    ];
  }

  public function newChatMessageNotification($message)
  {
    if ($message['sender_id'] == $this->selectedUser->id) {
      $messageObj = Message::find($message['id']);
      $this->messages->push($messageObj);
    }
  }

  public function render()
  {
    return view('livewire.admin-chat-box');
  }
}
