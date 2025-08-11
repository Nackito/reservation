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

    // Ajouter tous les canaux admin groupés (un par réservation)
    $adminChannels = \App\Models\Conversation::where('is_admin_channel', true)
      ->orderByDesc('created_at')->get();
    $adminUsers = collect();
    foreach ($adminChannels as $adminChannel) {
      $booking = $adminChannel->booking_id ? \App\Models\Booking::find($adminChannel->booking_id) : null;
      $propertyName = $booking && $booking->property ? $booking->property->name : 'Canal Admin';
      $adminUser = new \stdClass();
      $adminUser->id = 'admin_channel_' . $adminChannel->id;
      $adminUser->name = $propertyName;
      $adminUser->email = 'Canal de réservation';
      $adminUser->conversation_id = $adminChannel->id;
      $adminUsers->push($adminUser);
    }
    // Fusionner les canaux admin groupés et les utilisateurs privés
    $this->users = $adminUsers->concat($this->users)->values();

    $this->selectedUser = $this->users->first();
    $this->loadMessages();
    $this->loginID = Auth::id();

    //$first = $this->users->first();
    //$this->selectedUserId = $first?->id;
  }
  public function loadMessages()
  {
    if (str_starts_with($this->selectedUser->id, 'admin_channel_')) {
      // Charger les messages du canal admin groupé
      $conversationId = $this->selectedUser->conversation_id;
      $this->messages = Message::where('conversation_id', $conversationId)->orderBy('created_at')->get();
    } else {
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
  }

  public function selectUser($id)
  {
    if (str_starts_with($id, 'admin_channel_')) {
      $conversationId = (int)str_replace('admin_channel_', '', $id);
      $adminChannel = \App\Models\Conversation::find($conversationId);
      $booking = $adminChannel && $adminChannel->booking_id ? \App\Models\Booking::find($adminChannel->booking_id) : null;
      $propertyName = $booking && $booking->property ? $booking->property->name : 'Canal Admin';
      $adminUser = new \stdClass();
      $adminUser->id = 'admin_channel_' . $conversationId;
      $adminUser->name = $propertyName;
      $adminUser->email = 'Canal de réservation';
      $adminUser->conversation_id = $conversationId;
      $this->selectedUser = $adminUser;
    } else {
      $this->selectedUser = User::find($id);
    }
    $this->loadMessages();
  }

  public function submit()
  {
    if (!$this->newMessage) return;

    if (str_starts_with($this->selectedUser->id, 'admin_channel_')) {
      $conversationId = $this->selectedUser->conversation_id;
      $message = Message::create([
        'conversation_id' => $conversationId,
        'sender_id' => Auth::id(),
        'receiver_id' => 5, // ou null, selon la logique
        'content' => $this->newMessage,
      ]);
    } else {
      $message = Message::create([
        'sender_id' => Auth::id(),
        'receiver_id' => $this->selectedUser->id,
        'content' => $this->newMessage,
      ]);
    }

    $this->messages->push($message);
    $this->newMessage = '';
    broadcast(new MessageSent($message));
  }

  public function updatedNewMessage($value)
  {
    $this->dispatch('userTyping', userID: $this->loginID, userName: Auth::user()->name, selectedUserID: $this->selectedUser->id);
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
