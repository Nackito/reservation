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
  // Listes d'utilisateurs et canaux sous forme d'array sérialisable
  public $users; // array<array{id:string,name:string,email:string,conversation_id?:int}>
  public $selectedUser; // array{id:string,name:string,email:string,conversation_id?:int}|null
  public $newMessage;
  public $messages;
  public $loginID;

  public function mount()
  {
    // Charger tous les utilisateurs sauf l'admin sous forme d'array sérialisable
    $userItems = User::whereNot('id', Auth::id())
      ->latest()
      ->get()
      ->map(fn(User $u) => [
        'id' => (string) $u->id,
        'name' => $u->name,
        'email' => $u->email,
      ]);

    // Ajouter tous les canaux admin groupés (un par réservation)
    $adminChannels = \App\Models\Conversation::where('is_admin_channel', true)
      ->orderByDesc('created_at')->get();
    $adminUsers = collect();
    foreach ($adminChannels as $adminChannel) {
      $booking = $adminChannel->booking_id ? \App\Models\Booking::find($adminChannel->booking_id) : null;
      $propertyName = $booking && $booking->property ? $booking->property->name : 'Canal Admin';
      $adminUsers->push([
        'id' => 'admin_channel_' . $adminChannel->id,
        'name' => $propertyName,
        'email' => 'Canal de réservation',
        'conversation_id' => $adminChannel->id,
      ]);
    }
    // Fusionner les canaux admin groupés et les utilisateurs privés
    $this->users = $adminUsers->concat($userItems)->values()->all();

    $this->selectedUser = $this->users[0] ?? null;
    $this->messages = collect();
    $this->loadMessages();
    $this->loginID = Auth::id();

    //$first = $this->users->first();
    //$this->selectedUserId = $first?->id;
  }
  public function loadMessages()
  {
    if (!$this->selectedUser) {
      $this->messages = collect();
      return;
    }

    if (str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      // Charger les messages du canal admin groupé
      $conversationId = $this->selectedUser['conversation_id'] ?? null;
      $this->messages = Message::where('conversation_id', $conversationId)
        ->orderBy('created_at')
        ->get();
    } else {
      $peerId = (int) $this->selectedUser['id'];
      $this->messages = Message::query()
        ->where(function ($q) use ($peerId) {
          $q->where('sender_id', Auth::id())
            ->where('receiver_id', $peerId);
        })
        ->orWhere(function ($q) use ($peerId) {
          $q->where('sender_id', $peerId)
            ->where('receiver_id', Auth::id());
        })
        ->orderBy('created_at')
        ->get();
    }
  }

  public function selectUser($id)
  {
    // Retrouver l'entrée correspondante dans la liste existante pour rester sérialisable
    $found = collect($this->users)->firstWhere('id', (string) $id);
    if ($found) {
      $this->selectedUser = $found;
    } elseif (!str_starts_with((string) $id, 'admin_channel_')) {
      // Fallback: reconstruire à partir du modèle
      $user = User::find($id);
      if ($user) {
        $this->selectedUser = [
          'id' => (string) $user->id,
          'name' => $user->name,
          'email' => $user->email,
        ];
      }
    }
    $this->loadMessages();
  }

  public function submit()
  {
    if (!$this->newMessage) {
      return;
    }

    if ($this->selectedUser && str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      $conversationId = $this->selectedUser['conversation_id'] ?? null;
      $message = Message::create([
        'conversation_id' => $conversationId,
        'sender_id' => Auth::id(),
        'receiver_id' => 5, // ou null, selon la logique
        'content' => $this->newMessage,
      ]);
    } else {
      $message = Message::create([
        'sender_id' => Auth::id(),
        'receiver_id' => (int) ($this->selectedUser['id'] ?? 0),
        'content' => $this->newMessage,
      ]);
    }

    $this->messages = $this->messages instanceof \Illuminate\Support\Collection ? $this->messages : collect($this->messages);
    $this->messages->push($message);
    $this->newMessage = '';
    broadcast(new MessageSent($message));
  }

  public function updatedNewMessage($value)
  {
    if ($this->selectedUser) {
      $this->dispatch('userTyping', userID: $this->loginID, userName: Auth::user()->name, selectedUserID: $this->selectedUser['id']);
    }
  }

  public function getListeners()
  {
    return [
      "echo-private:chat.{$this->loginID},MessageSent" => "newChatMessageNotification"
    ];
  }

  public function newChatMessageNotification($message)
  {
    if (!$this->selectedUser) {
      return;
    }

    // Pour un canal admin groupé, on se base sur conversation_id
    if (str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      if (($message['conversation_id'] ?? null) == ($this->selectedUser['conversation_id'] ?? null)) {
        $messageObj = Message::find($message['id']);
        $this->messages->push($messageObj);
      }
      return;
    }

    // Discussion directe: on vérifie l'expéditeur
    if ((string) ($message['sender_id'] ?? '') === (string) $this->selectedUser['id']) {
      $messageObj = Message::find($message['id']);
      $this->messages->push($messageObj);
    }
  }

  public function render()
  {
    return view('livewire.admin-chat-box');
  }
}
