<?php

namespace App\Livewire;

use App\Filament\Pages\Chat;
use Livewire\Component;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminChatBox extends Component
{
  private const DATE_BADGE_FORMAT = 'D MMM YY';
  // Listes d'utilisateurs et canaux sous forme d'array sérialisable
  public $users; // array<array{id:string,name:string,email:string,conversation_id?:int,last_preview?:string,last_at?:string,last_at_sort?:int}>
  public $selectedUser; // array{id:string,name:string,email:string,conversation_id?:int}|null
  public $newMessage;
  public $messages;
  public $loginID;
  public $lastSeen = [];

  public function mount()
  {
    // Charger tous les utilisateurs sauf l'admin sous forme d'array sérialisable
    $userItems = User::whereNot('id', Auth::id())
      ->latest()
      ->get()
      ->map(function (User $u) {
        // Dernier message de la discussion directe
        $last = Message::query()
          ->where(function ($q) use ($u) {
            $q->where('sender_id', Auth::id())->where('receiver_id', $u->id);
          })
          ->orWhere(function ($q) use ($u) {
            $q->where('sender_id', $u->id)->where('receiver_id', Auth::id());
          })
          ->latest('created_at')
          ->first();

        $preview = $last?->content ? \Illuminate\Support\Str::limit($last->content, 55) : '';
        $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
        $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;

        return [
          'id' => (string) $u->id,
          'name' => $u->name,
          'email' => $u->email,
          'last_preview' => $preview,
          'last_at' => $lastAt,
          'last_at_sort' => $lastAtSort,
          'last_sender_id' => $last?->sender_id,
        ];
      });

    // Ajouter tous les canaux admin groupés (un par réservation)
    $adminChannels = \App\Models\Conversation::where('is_admin_channel', true)
      ->orderByDesc('created_at')->get();
    $adminUsers = collect();
    foreach ($adminChannels as $adminChannel) {
      $booking = $adminChannel->booking_id ? \App\Models\Booking::find($adminChannel->booking_id) : null;
      $propertyName = $booking && $booking->property ? $booking->property->name : 'Canal Admin';
      $last = Message::where('conversation_id', $adminChannel->id)->latest('created_at')->first();
      $preview = $last?->content ? \Illuminate\Support\Str::limit($last->content, 55) : '';
      $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
      $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;
      $adminUsers->push([
        'id' => 'admin_channel_' . $adminChannel->id,
        'name' => $propertyName,
        'email' => 'Canal de réservation',
        'conversation_id' => $adminChannel->id,
        'last_preview' => $preview,
        'last_at' => $lastAt,
        'last_at_sort' => $lastAtSort,
        'last_sender_id' => $last?->sender_id,
      ]);
    }
    // Fusionner les canaux admin groupés et les utilisateurs privés
    $this->users = $adminUsers->concat($userItems)->values()->all();
    // Trier par date du dernier message décroissante
    usort($this->users, function ($a, $b) {
      return ($b['last_at_sort'] ?? 0) <=> ($a['last_at_sort'] ?? 0);
    });

    // Ne pas pré-sélectionner: on attend le clic utilisateur
    $this->selectedUser = null;
    $this->messages = collect();
    $this->loginID = Auth::id();
    if ($this->selectedUser) {
      $this->lastSeen[$this->selectedUser['id']] = time();
    }

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
    // Passer en mode chat et marquer comme vu maintenant
    $this->showChat = true;
    $this->lastSeen[(string)$id] = time();
    // Demander le focus sur l'input message côté navigateur
    $this->dispatch('focusMessageInput');
  }

  public $showChat = false;

  public function backToList(): void
  {
    $this->showChat = false;
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

    // Mettre à jour l'aperçu et la date pour la conversation sélectionnée
    if ($this->selectedUser) {
      $this->bumpConversationMeta($this->selectedUser['id'], $message);
      $this->lastSeen[$this->selectedUser['id']] = time();
    }

    $this->newMessage = '';
    broadcast(new MessageSent($message));
    // Re-focuser l'input après envoi
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
      // Mettre à jour la vignette de la conversation correspondante
      if (isset($message['conversation_id'])) {
        $this->bumpConversationMeta('admin_channel_' . $message['conversation_id'], Message::find($message['id']));
      }
      return;
    }

    // Discussion directe: on vérifie l'expéditeur
    if ((string) ($message['sender_id'] ?? '') === (string) $this->selectedUser['id']) {
      $messageObj = Message::find($message['id']);
      $this->messages->push($messageObj);
    }
    // Bump aussi la conversation (pair = sender ou receiver différent de moi)
    $peerId = (string) (($message['sender_id'] ?? null) == Auth::id() ? ($message['receiver_id'] ?? '') : ($message['sender_id'] ?? ''));
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
    return view('livewire.admin-chat-box');
  }
}
