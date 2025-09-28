<?php

namespace App\Livewire;

use App\Filament\Pages\Chat;
use Livewire\Component;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Livewire\Attributes\On;

class AdminChatBox extends Component
{
  private const DATE_BADGE_FORMAT = 'D MMM YY';
  // Listes d'utilisateurs et canaux sous forme d'array sérialisable
  public $users; // array<array{id:string,name:string,email:string,conversation_id?:int,last_preview?:string,last_at?:string,last_at_sort?:int}>
  // Listes partitionnées pour l'onglet « Archivées » (seulement canaux de réservation)
  public $usersActive = [];
  public $usersArchived = [];
  public $activeTab = 'active'; // 'active' | 'archived'
  public $selectedUser; // array{id:string,name:string,email:string,conversation_id?:int}|null
  public $newMessage;
  public $messages;
  public $loginID;
  public $lastSeen = [];
  // Métadonnées pour l'affichage: barre non-lus et indicateur "Vu"
  public $firstUnreadMessageId = null; // id du premier message entrant non lu
  public $unreadCount = 0; // nombre de messages entrants non lus
  public $lastOutgoingSeenMessageId = null; // id du dernier message sortant considéré "vu"
  public $lastOutgoingSeenAt = null; // heure de "vu" (string HH:mm)

  public function mount()
  {
    // Charger les items utilisateurs privés et canaux admin
    $userItems = $this->buildPrivateUserItems();
    $adminUsers = $this->buildAdminChannelItems();

    // Partitionner canaux admin entre actifs et archivés
    $adminActive = [];
    $adminArchived = [];
    foreach ($adminUsers as $item) {
      if (!empty($item['archived'])) {
        $adminArchived[] = $item;
      } else {
        $adminActive[] = $item;
      }
    }

    // Marquer l'archivage des discussions directes si inactives depuis X jours
    $directInactiveDays = (int) config('chat.archive.direct_inactive_days', 14);
    $nowTs = Carbon::now()->getTimestamp();
    $inactivityThreshold = $nowTs - ($directInactiveDays * 86400);
    foreach ($userItems as &$ui) {
      $lastTs = (int) ($ui['last_at_sort'] ?? 0);
      $ui['archived'] = $lastTs > 0 ? ($lastTs < $inactivityThreshold) : true; // si jamais aucune activité, considérer archivé
    }
    unset($ui);

    // Actifs = canaux admin actifs + discussions directes
    $activeDirects = array_values(array_filter($userItems, fn($u) => empty($u['archived'])));
    $archivedDirects = array_values(array_filter($userItems, fn($u) => !empty($u['archived'])));
    $this->usersActive = array_values(array_merge($adminActive, $activeDirects));
    $this->sortArrayByLastAt($this->usersActive);

    // Archivés = canaux admin archivés + discussions directes archivées
    $this->usersArchived = array_values(array_merge($adminArchived, $archivedDirects));
    $this->sortArrayByLastAt($this->usersArchived);

    // Liste affichée selon l'onglet courant
    $this->users = $this->usersActive;
    // Mémoriser onglet par défaut
    session(['admin_chat.tab' => 'active']);

    // Ne pas pré-sélectionner: on attend le clic utilisateur
    $this->selectedUser = null;
    $this->messages = collect();
    $this->loginID = Auth::id();
    if ($this->selectedUser) {
      $this->lastSeen[$this->selectedUser['id']] = time();
    }

    // Initialiser les métadonnées d'affichage
    $this->firstUnreadMessageId = null;
    $this->unreadCount = 0;
    $this->lastOutgoingSeenMessageId = null;
    $this->lastOutgoingSeenAt = null;

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
    // Chercher dans la liste visible puis dans l'autre (archivées/actives)
    $found = collect($this->users)->firstWhere('id', (string) $id);
    if (!$found) {
      $found = collect($this->usersActive)->firstWhere('id', (string) $id) ?? collect($this->usersArchived)->firstWhere('id', (string) $id);
    }
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
    // Mémoriser la sélection (pour l'action Filament côté page)
    session(['admin_chat.selected' => (string) $id]);
    // Conserver l'ancien lastSeen pour calculer la barre des non-lus
    $previousSeen = $this->lastSeen[(string)$id] ?? 0;
    $this->loadMessages();
    $this->computeConversationMeta($previousSeen);
    // Passer en mode chat et marquer comme vu maintenant
    $this->showChat = true;
    $this->lastSeen[(string)$id] = time();
    // Demander le focus sur l'input message côté navigateur
    $this->dispatch('focusMessageInput');
    $this->dispatch('scrollToBottom');
    // Notifier la page Filament qu'une conversation est sélectionnée
    $this->dispatch('adminChatSelected');
  }

  public $showChat = false;

  public function backToList(): void
  {
    $this->showChat = false;
    session()->forget('admin_chat.selected');
    // Notifier la page Filament qu'il n'y a plus de sélection
    $this->dispatch('adminChatCleared');
  }

  public function submit()
  {
    if (!$this->newMessage) {
      return;
    }

    if ($this->selectedUser && str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      $conversationId = $this->selectedUser['conversation_id'] ?? null;
      // Envoyer au véritable utilisateur lié à la conversation (et non à l'admin)
      $conv = $conversationId ? \App\Models\Conversation::find($conversationId) : null;
      $targetUserId = $conv && isset($conv->user_id) ? (int) $conv->user_id : 0; // utilisateur propriétaire de la conversation
      if ($targetUserId <= 0) {
        // Impossible de déterminer le destinataire, on annule l'envoi
        return;
      }
      $message = Message::create([
        'conversation_id' => $conversationId,
        'sender_id' => Auth::id(),
        'receiver_id' => $targetUserId, // l'utilisateur reçoit les messages du canal admin
        'content' => $this->newMessage,
      ]);
      // Notification email utilisateur
      try {
        $recipient = \App\Models\User::find($targetUserId);
        if ($recipient && !empty($recipient->email)) {
          $recipient->notify(new \App\Notifications\MessageReceivedNotification($message));
        }
      } catch (\Throwable $e) {
        // ne bloque pas l'envoi du message si l'email échoue
      }
    } else {
      $message = Message::create([
        'sender_id' => Auth::id(),
        'receiver_id' => (int) ($this->selectedUser['id'] ?? 0),
        'content' => $this->newMessage,
      ]);
      // Notification email destinataire direct
      try {
        $recipient = \App\Models\User::find($message->receiver_id);
        if ($recipient && !empty($recipient->email)) {
          $recipient->notify(new \App\Notifications\MessageReceivedNotification($message));
        }
      } catch (\Throwable $e) {
        // ignorer les erreurs d'email
      }
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
    $this->dispatch('scrollToBottom');
  }

  public function updatedNewMessage($value)
  {
    if ($this->selectedUser) {
      if (str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
        $conversationId = $this->selectedUser['conversation_id'] ?? null;
        $conv = $conversationId ? \App\Models\Conversation::find($conversationId) : null;
        $receiverId = $conv && isset($conv->user_id) ? (int) $conv->user_id : 0; // diffuser l'indicateur à l'utilisateur concerné
      } else {
        $receiverId = (int) $this->selectedUser['id'];
      }
      if ($receiverId > 0) {
        event(new \App\Events\UserTyping(Auth::id(), $receiverId, Auth::user()->name));
      }
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
    // Toujours: mettre à jour la vignette dans les listes (aperçu/date/ordre + non-lu)
    $messageObj = Message::find($message['id'] ?? 0);
    if ($messageObj) {
      if (isset($message['conversation_id']) && $message['conversation_id']) {
        $this->bumpConversationMeta('admin_channel_' . $message['conversation_id'], $messageObj);
      } else {
        $peerId = (string) ((($message['sender_id'] ?? null) == Auth::id()) ? ($message['receiver_id'] ?? '') : ($message['sender_id'] ?? ''));
        if ($peerId !== '') {
          $this->bumpConversationMeta($peerId, $messageObj);
        }
      }
    }

    // Si aucune conversation n'est ouverte, on s'arrête là (liste déjà rafraîchie)
    if (!$this->selectedUser) {
      return;
    }

    // Si un canal admin est ouvert et correspond au message, insérer le message + maj "vu"
    if (str_starts_with($this->selectedUser['id'], 'admin_channel_')) {
      if (($message['conversation_id'] ?? null) == ($this->selectedUser['conversation_id'] ?? null) && $messageObj) {
        $this->messages->push($messageObj);
        $this->lastSeen[$this->selectedUser['id']] = max($this->lastSeen[$this->selectedUser['id']] ?? 0, $messageObj->created_at?->getTimestamp() ?? time());
        $this->computeConversationMeta($this->lastSeen[$this->selectedUser['id']] ?? 0);
      }
      return;
    }

    // Discussion directe: si la conversation ouverte est l'expéditeur, insérer + maj "vu"
    if ((string) ($message['sender_id'] ?? '') === (string) ($this->selectedUser['id'] ?? '')) {
      if ($messageObj) {
        $this->messages->push($messageObj);
        $this->lastSeen[$this->selectedUser['id']] = max($this->lastSeen[$this->selectedUser['id']] ?? 0, $messageObj->created_at?->getTimestamp() ?? time());
        $this->computeConversationMeta($this->lastSeen[$this->selectedUser['id']] ?? 0);
      }
    }
  }

  #[On('openConversation')]
  public function openConversation($id): void
  {
    if ($id === null || $id === '') {
      return;
    }
    $this->selectUser((string) $id);
  }

  #[On('deleteCurrentConversation')]
  public function deleteCurrentConversation(): void
  {
    if (!$this->selectedUser) {
      return;
    }

    $entry = $this->selectedUser;
    if (str_starts_with($entry['id'], 'admin_channel_')) {
      $conversationId = (int) ($entry['conversation_id'] ?? 0);
      if ($conversationId > 0) {
        // Supprimer messages et la conversation liée
        \App\Models\Message::where('conversation_id', $conversationId)->delete();
        if ($conv = \App\Models\Conversation::find($conversationId)) {
          $conv->delete();
        }
      }
    } else {
      $peerId = (int) $entry['id'];
      if ($peerId > 0) {
        \App\Models\Message::query()
          ->where(function ($q) use ($peerId) {
            $q->where('sender_id', Auth::id())->where('receiver_id', $peerId);
          })
          ->orWhere(function ($q) use ($peerId) {
            $q->where('sender_id', $peerId)->where('receiver_id', Auth::id());
          })
          ->delete();
      }
    }

    // Retirer des listes et ré-initialiser l'état
    $this->usersActive = array_values(array_filter($this->usersActive, fn($u) => ($u['id'] ?? null) !== $entry['id']));
    $this->usersArchived = array_values(array_filter($this->usersArchived, fn($u) => ($u['id'] ?? null) !== $entry['id']));
    $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;

    $this->selectedUser = null;
    $this->messages = collect();
    $this->showChat = false;
    session()->forget('admin_chat.selected');
    $this->dispatch('adminChatCleared');
  }

  #[On('bulkDeleteConversations')]
  public function bulkDeleteConversations(array $ids): void
  {
    foreach ($ids as $rawId) {
      $id = (string) $rawId;
      if (str_starts_with($id, 'admin_channel_')) {
        $conversationId = (int) str_replace('admin_channel_', '', $id);
        if ($conversationId > 0) {
          \App\Models\Message::where('conversation_id', $conversationId)->delete();
          if ($conv = \App\Models\Conversation::find($conversationId)) {
            $conv->delete();
          }
        }
      } else {
        $peerId = (int) $id;
        if ($peerId > 0) {
          \App\Models\Message::query()
            ->where(function ($q) use ($peerId) {
              $q->where('sender_id', Auth::id())->where('receiver_id', $peerId);
            })
            ->orWhere(function ($q) use ($peerId) {
              $q->where('sender_id', $peerId)->where('receiver_id', Auth::id());
            })
            ->delete();
        }
      }

      // Nettoyer les listes et la sélection en cours si concernée
      $this->usersActive = array_values(array_filter($this->usersActive, fn($u) => ($u['id'] ?? null) !== $id));
      $this->usersArchived = array_values(array_filter($this->usersArchived, fn($u) => ($u['id'] ?? null) !== $id));
      if ($this->selectedUser && ($this->selectedUser['id'] ?? null) === $id) {
        $this->selectedUser = null;
        $this->messages = collect();
        $this->showChat = false;
        session()->forget('admin_chat.selected');
      }
    }

    $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
    // Après suppression multiple, on reste sur la liste
    $this->dispatch('adminChatCleared');
  }

  private function bumpConversationMeta(string $id, Message $message): void
  {
    // Mettre à jour dans la liste visible si présent
    $updated = false;
    foreach ($this->users as &$u) {
      if ($u['id'] === $id) {
        $u['last_preview'] = \Illuminate\Support\Str::limit($message->content, 55);
        $u['last_at'] = $message->created_at ? $message->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
        $u['last_at_sort'] = $message->created_at ? $message->created_at->getTimestamp() : time();
        $u['last_sender_id'] = $message->sender_id;
        if ($this->selectedUser && $this->selectedUser['id'] === $id) {
          $this->selectedUser = $u;
        }
        $updated = true;
        break;
      }
    }
    unset($u);

    // Et dans les listes sources actives/archivées
    foreach ($this->usersActive as &$ua) {
      if ($ua['id'] === $id) {
        $ua['last_preview'] = \Illuminate\Support\Str::limit($message->content, 55);
        $ua['last_at'] = $message->created_at ? $message->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
        $ua['last_at_sort'] = $message->created_at ? $message->created_at->getTimestamp() : time();
        $ua['last_sender_id'] = $message->sender_id;
        $updated = true;
        break;
      }
    }
    unset($ua);
    if (!$updated) {
      foreach ($this->usersArchived as &$ur) {
        if ($ur['id'] === $id) {
          $ur['last_preview'] = \Illuminate\Support\Str::limit($message->content, 55);
          $ur['last_at'] = $message->created_at ? $message->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
          $ur['last_at_sort'] = $message->created_at ? $message->created_at->getTimestamp() : time();
          $ur['last_sender_id'] = $message->sender_id;
          break;
        }
      }
      unset($ur);
    }

    // Trier listes et re-référencer la liste affichée
    $this->sortArrayByLastAt($this->usersActive);
    $this->sortArrayByLastAt($this->usersArchived);
    $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
  }

  /**
   * Calcule les métadonnées d'affichage pour la conversation sélectionnée:
   * - premier message entrant non lu et son compteur
   * - dernier message sortant considéré comme "vu" (heuristique: réponse reçue après)
   * @param int|null $previousSeenTs timestamp lastSeen avant ouverture (si null, utilise l'état courant)
   */
  private function computeConversationMeta(?int $previousSeenTs = null): void
  {
    // Reset meta
    $this->firstUnreadMessageId = null;
    $this->unreadCount = 0;
    $this->lastOutgoingSeenMessageId = null;
    $this->lastOutgoingSeenAt = null;

    if (!$this->selectedUser) {
      return;
    }

    $seenThreshold = $previousSeenTs ?? ($this->lastSeen[$this->selectedUser['id']] ?? 0);
    $isAdminChannel = str_starts_with($this->selectedUser['id'], 'admin_channel_');
    $me = Auth::id();

    // Deux passes indépendantes pour réduire la complexité
    $this->computeUnreadMetaForMessages($this->messages, $seenThreshold, $me);
    $this->computeSeenMetaForMessages($this->messages, $isAdminChannel, $me);
  }

  // --- Helpers d'extraction pour réduire la complexité ---

  private function buildPrivateUserItems(): array
  {
    return User::whereNot('id', Auth::id())
      ->latest()
      ->get()
      ->map(function (User $u) {
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
      })
      ->values()
      ->all();
  }

  private function buildAdminChannelItems(): array
  {
    $adminChannels = \App\Models\Conversation::where('is_admin_channel', true)
      ->orderByDesc('created_at')
      ->get();

    $items = [];
    foreach ($adminChannels as $adminChannel) {
      $booking = $adminChannel->booking_id ? \App\Models\Booking::find($adminChannel->booking_id) : null;
      $propertyName = $booking && $booking->property ? $booking->property->name : 'Canal Admin';
      // Archiver N jours après la fin du séjour si on a une réservation liée
      $archived = false;
      if ($booking && !empty($booking->end_date)) {
        try {
          $grace = (int) config('chat.archive.booking_grace_days', 2);
          $expiry = Carbon::parse($booking->end_date)->endOfDay()->addDays($grace);
          $archived = Carbon::now()->greaterThan($expiry);
        } catch (\Throwable $e) {
          $archived = false;
        }
      }
      $last = Message::where('conversation_id', $adminChannel->id)->latest('created_at')->first();
      $preview = $last?->content ? \Illuminate\Support\Str::limit($last->content, 55) : '';
      $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
      $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;
      $items[] = [
        'id' => 'admin_channel_' . $adminChannel->id,
        'name' => $propertyName,
        'email' => 'Canal de réservation',
        'conversation_id' => $adminChannel->id,
        'last_preview' => $preview,
        'last_at' => $lastAt,
        'last_at_sort' => $lastAtSort,
        'last_sender_id' => $last?->sender_id,
        'archived' => $archived,
      ];
    }
    return $items;
  }

  // Méthode de tri globale supprimée (utiliser sortArrayByLastAt sur listes ciblées)

  private function sortArrayByLastAt(array &$arr): void
  {
    usort($arr, function ($a, $b) {
      return ($b['last_at_sort'] ?? 0) <=> ($a['last_at_sort'] ?? 0);
    });
  }

  public function switchTab(string $tab): void
  {
    $tab = in_array($tab, ['active', 'archived'], true) ? $tab : 'active';
    $this->activeTab = $tab;
    $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
    session(['admin_chat.tab' => $this->activeTab]);
  }

  private function computeUnreadMetaForMessages($messages, int $seenThreshold, int $me): void
  {
    $this->firstUnreadMessageId = null;
    $this->unreadCount = 0;
    foreach ($messages as $m) {
      $ts = $m->created_at ? $m->created_at->getTimestamp() : 0;
      $isMine = (int)$m->sender_id === (int)$me;
      if (!$isMine && $ts > $seenThreshold) {
        if ($this->firstUnreadMessageId === null) {
          $this->firstUnreadMessageId = $m->id;
        }
        $this->unreadCount++;
      }
    }
  }

  private function computeSeenMetaForMessages($messages, bool $isAdminChannel, int $me): void
  {
    $this->lastOutgoingSeenMessageId = null;
    $this->lastOutgoingSeenAt = null;
    if ($isAdminChannel) {
      return; // désactivé pour canaux admin
    }
    $lastMyMessage = null;
    foreach ($messages as $m) {
      $isMine = (int)$m->sender_id === (int)$me;
      if ($isMine) {
        $lastMyMessage = $m;
      } else {
        if ($lastMyMessage && ($m->created_at && $lastMyMessage->created_at && $m->created_at->gt($lastMyMessage->created_at))) {
          $this->lastOutgoingSeenMessageId = $lastMyMessage->id;
          $this->lastOutgoingSeenAt = $m->created_at->format('H:i');
        }
      }
    }
  }

  public function render()
  {
    return view('livewire.admin-chat-box');
  }
}
