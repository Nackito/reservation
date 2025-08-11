<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;

class ChatBox extends Component
{
  public $newMessage;
  public $users;
  public $selectedUser;
  public $messages;
  public $loginID;

  public function mount()
  {
    $userId = Auth::id();
    // 1. Récupérer les conversations directes (messages privés)
    $directUserIds = Message::where(function ($q) use ($userId) {
      $q->where('sender_id', $userId)->orWhere('receiver_id', $userId);
    })
      ->get()
      ->map(function ($msg) use ($userId) {
        return $msg->sender_id == $userId ? $msg->receiver_id : $msg->sender_id;
      })
      ->unique()
      ->filter(fn($id) => $id != $userId)
      ->values();
    $directUsers = User::whereIn('id', $directUserIds)->get();

    // 2. Récupérer tous les canaux admin groupés liés à l'utilisateur (un par réservation)
    $adminChannels = Conversation::where('is_admin_channel', true)
      ->where('user_id', $userId)
      ->whereHas('messages')
      ->get();

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

    // Fusionner les deux types de conversations dans $this->users
    $this->users = collect();
    foreach ($adminUsers as $adminUser) {
      $this->users->push($adminUser);
    }
    foreach ($directUsers as $user) {
      $this->users->push($user);
    }

    if ($this->users->isNotEmpty()) {
      $this->selectedUser = $this->users->first();
      $this->loadMessages();
    } else {
      $this->selectedUser = null;
      $this->messages = collect();
    }
    $this->loginID = Auth::id();
  }

  public function loadMessages()
  {
    if (str_starts_with($this->selectedUser->id, 'admin_channel_')) {
      // Charger tous les messages du canal admin groupé (par conversation_id)
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

  public function submit()
  {
    if (!$this->newMessage) return;

    if (str_starts_with($this->selectedUser->id, 'admin_channel_')) {
      // Envoi dans le canal admin groupé : rattacher au canal, receiver_id=5 (placeholder)
      $conversationId = $this->selectedUser->conversation_id;
      $message = Message::create([
        'conversation_id' => $conversationId,
        'sender_id' => Auth::id(),
        'receiver_id' => 5,
        'content' => $this->newMessage,
      ]);
    } else {
      $message = Message::create([
        'sender_id' => Auth::id(),
        'receiver_id' => $this->selectedUser->id,
        'content' => $this->newMessage,
      ]);
    }

    $this->newMessage = '';
    $this->messages->push($message);

    broadcast(new MessageSent($message));
  }

  public function updatedNewMessage($value)
  {
    $this->dispatch('userTyping', userID: $this->loginID, userName: Auth::user()->name, selectedUserID: $this->selectedUser->id);
  }

  public function getListeners()
  {
    return [
      "echo-private:chat.{$this->loginID},MessageSent" => 'newChatMessageNotification',
    ];
  }

  public function newChatMessageNotification($message)
  {
    if ($message['sender_id'] == $this->selectedUser->id) {
      $messageObj = Message::find($message['id']);
      $this->messages->push($messageObj);
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

  public function render()
  {
    return view('livewire.chat-box');
  }
}
