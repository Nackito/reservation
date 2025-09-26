<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ChatBox extends Component
{
  private const DATE_BADGE_FORMAT = 'D MMM YY';
  // Données sérialisables (alignées avec AdminChatBox)
  public $users; // array<array{id:string,name:string,email:string,conversation_id?:int,last_preview?:string,last_at?:string,last_at_sort?:int,last_sender_id?:int}>
  public $selectedUser; // array{id:string,name:string,email:string,conversation_id?:int}|null
  public $messages;
  public $newMessage;
  public $loginID;
  public $showChat = false;
  public $lastSeen = [];

  public function mount()
  {
    $this->users = $this->buildUserAndChannelItems();
    // Trier par récence
    usort($this->users, function ($a, $b) {
      return ($b['last_at_sort'] ?? 0) <=> ($a['last_at_sort'] ?? 0);
    });

    // Pas de pré-sélection: mono-colonne façon admin
    $this->selectedUser = null;
    $this->messages = collect();
    $this->newMessage = '';
    $this->loginID = Auth::id();
  }

  private function buildUserAndChannelItems(): array
  {
    $direct = $this->buildDirectItems();
    $admin = $this->buildAdminItems();
    return array_merge($admin, $direct);
  }

  private function buildDirectItems(): array
  {
    $userId = Auth::id();
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
    return $directUsers->map(function (User $u) use ($userId) {
      $last = Message::query()
        ->where(function ($q) use ($userId, $u) {
          $q->where('sender_id', $userId)->where('receiver_id', $u->id);
        })
        ->orWhere(function ($q) use ($userId, $u) {
          $q->where('sender_id', $u->id)->where('receiver_id', $userId);
        })
        ->latest('created_at')
        ->first();

      $preview = $last?->content ? \Illuminate\Support\Str::limit($last->content, 55) : '';
      $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
      $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;

      return [
        'id' => (string)$u->id,
        'name' => $u->name,
        'email' => $u->email,
        'last_preview' => $preview,
        'last_at' => $lastAt,
        'last_at_sort' => $lastAtSort,
        'last_sender_id' => $last?->sender_id,
      ];
    })->values()->all();
  }

  private function buildAdminItems(): array
  {
    $userId = Auth::id();
    $channels = Conversation::where('is_admin_channel', true)
      ->where('user_id', $userId)
      ->whereHas('messages')
      ->orderByDesc('created_at')
      ->get();

    $items = [];
    foreach ($channels as $channel) {
      $booking = $channel->booking_id ? \App\Models\Booking::find($channel->booking_id) : null;

      // Masquer côté utilisateur les conversations de réservation 2 jours après la date de sortie
      $expired = false;
      if ($booking && $booking->end_date) {
        $end = $booking->end_date instanceof \Carbon\Carbon ? $booking->end_date->copy() : \Carbon\Carbon::parse($booking->end_date);
        $cutoff = $end->endOfDay()->addDays(2);
        $expired = \Carbon\Carbon::now()->greaterThan($cutoff);
      }
      if ($expired) {
        continue;
      }

      $propertyName = $booking && $booking->property ? $booking->property->name : 'Canal Admin';
      $last = Message::where('conversation_id', $channel->id)->latest('created_at')->first();
      $preview = $last?->content ? \Illuminate\Support\Str::limit($last->content, 55) : '';
      $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
      $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;
      $items[] = [
        'id' => 'admin_channel_' . $channel->id,
        'name' => $propertyName,
        'email' => 'Canal de réservation',
        'conversation_id' => $channel->id,
        'last_preview' => $preview,
        'last_at' => $lastAt,
        'last_at_sort' => $lastAtSort,
        'last_sender_id' => $last?->sender_id,
      ];
    }
    return $items;
  }

  public function loadMessages()
  {
    if (!$this->selectedUser) {
      $this->messages = collect();
      return;
    }

    if (str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      $conversationId = $this->selectedUser['conversation_id'] ?? null;
      $this->messages = Message::where('conversation_id', $conversationId)
        ->orderBy('created_at')
        ->get();
    } else {
      $peerId = (int)$this->selectedUser['id'];
      $this->messages = Message::query()
        ->where(function ($q) use ($peerId) {
          $q->where('sender_id', Auth::id())->where('receiver_id', $peerId);
        })
        ->orWhere(function ($q) use ($peerId) {
          $q->where('sender_id', $peerId)->where('receiver_id', Auth::id());
        })
        ->orderBy('created_at')
        ->get();
    }
  }

  public function selectUser($id)
  {
    $found = collect($this->users)->firstWhere('id', (string)$id);
    if ($found) {
      $this->selectedUser = $found;
    } elseif (!str_starts_with((string)$id, 'admin_channel_')) {
      $u = User::find($id);
      if ($u) {
        $this->selectedUser = [
          'id' => (string)$u->id,
          'name' => $u->name,
          'email' => $u->email,
        ];
      }
    }
    $this->loadMessages();
    $this->showChat = true;
    $this->lastSeen[(string)$id] = time();
    $this->dispatch('focusMessageInput');
  }

  public function backToList(): void
  {
    $this->showChat = false;
  }

  public function submit()
  {
    if (!$this->newMessage || !$this->selectedUser) {
      return;
    }

    if (str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      $conversationId = $this->selectedUser['conversation_id'] ?? null;
      $message = Message::create([
        'conversation_id' => $conversationId,
        'sender_id' => Auth::id(),
        'receiver_id' => 5,
        'content' => $this->newMessage,
      ]);
    } else {
      $message = Message::create([
        'sender_id' => Auth::id(),
        'receiver_id' => (int)$this->selectedUser['id'],
        'content' => $this->newMessage,
      ]);
    }

    $this->messages = $this->messages instanceof \Illuminate\Support\Collection ? $this->messages : collect($this->messages);
    $this->messages->push($message);

    // Bump aperçu/date de la conversation
    if ($this->selectedUser) {
      $this->bumpConversationMeta($this->selectedUser['id'], $message);
      $this->lastSeen[$this->selectedUser['id']] = time();
    }

    $this->newMessage = '';
    broadcast(new MessageSent($message));
    $this->dispatch('focusMessageInput');
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
      "echo-private:chat.{$this->loginID},MessageSent" => 'newChatMessageNotification',
    ];
  }

  public function newChatMessageNotification($message)
  {
    if (!$this->selectedUser) {
      return;
    }

    if (str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      if (($message['conversation_id'] ?? null) == ($this->selectedUser['conversation_id'] ?? null)) {
        $messageObj = Message::find($message['id']);
        $this->messages->push($messageObj);
        $this->lastSeen[$this->selectedUser['id']] = max($this->lastSeen[$this->selectedUser['id']] ?? 0, $messageObj->created_at?->getTimestamp() ?? time());
      }
      if (isset($message['conversation_id'])) {
        $this->bumpConversationMeta('admin_channel_' . $message['conversation_id'], Message::find($message['id']));
      }
      return;
    }

    if ((string)($message['sender_id'] ?? '') === (string)$this->selectedUser['id']) {
      $messageObj = Message::find($message['id']);
      $this->messages->push($messageObj);
      $this->lastSeen[$this->selectedUser['id']] = max($this->lastSeen[$this->selectedUser['id']] ?? 0, $messageObj->created_at?->getTimestamp() ?? time());
    }

    $peerId = (string)(($message['sender_id'] ?? null) == Auth::id() ? ($message['receiver_id'] ?? '') : ($message['sender_id'] ?? ''));
    if ($peerId !== '') {
      $this->bumpConversationMeta($peerId, Message::find($message['id']));
    }
  }

  private function bumpConversationMeta(string $id, Message $message): void
  {
    foreach ($this->users as &$u) {
      if ($u['id'] === $id) {
        $u['last_preview'] = \Illuminate\Support\Str::limit($message->content, 55);
        $u['last_at'] = $message->created_at ? $message->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
        $u['last_at_sort'] = $message->created_at ? $message->created_at->getTimestamp() : time();
        if ($this->selectedUser && $this->selectedUser['id'] === $id) {
          $this->selectedUser = $u;
        }
        break;
      }
    }
    unset($u);
    usort($this->users, function ($a, $b) {
      return ($b['last_at_sort'] ?? 0) <=> ($a['last_at_sort'] ?? 0);
    });
  }

  public function render()
  {
    return view('livewire.chat-box');
  }
}
