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
use App\Models\Conversation;

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

  /**
   * Initialise l'état du composant:
   * - Construit les listes (actives/archivées) à partir des discussions directes et canaux admin
   * - Trie par récence
   * - Réinitialise la sélection et les métadonnées d'affichage (non-lus, vu)
   */
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
  /**
   * Charge les messages correspondant à l'entrée sélectionnée:
   * - Canal admin (par conversation_id)
   * - Discussion directe (moi <-> pair)
   */
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

  /**
   * Sélectionne une entrée (utilisateur ou canal admin) et prépare l'affichage:
   * - Recherche dans les listes existantes, sinon fallback (chargement DB)
   * - Met à jour lastSeen et calcule les métadonnées (non-lus, vu)
   * - Déclenche focus input et scroll en bas
   */
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
    } elseif (str_starts_with((string) $id, 'admin_channel_')) {
      // Fallback: id correspond à un canal admin fraîchement créé mais non présent en mémoire
      $convId = (int) str_replace('admin_channel_', '', (string) $id);
      $conv = $convId > 0 ? \App\Models\Conversation::find($convId) : null;
      if ($conv) {
        $booking = $conv->booking_id ? \App\Models\Booking::find($conv->booking_id) : null;
        $baseUserName = $conv->user?->name ?? 'Utilisateur';
        $propertyName = $baseUserName;
        if ($booking && $booking->property && !empty($booking->property->name)) {
          $propertyName = $booking->property->name . ' - ' . $baseUserName;
        }
        $last = Message::where('conversation_id', $conv->id)->latest('created_at')->first();
        $preview = $last?->content ? Str::limit($last->content, 55) : '';
        $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
        $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;
        $entry = [
          'id' => 'admin_channel_' . $conv->id,
          'name' => $propertyName,
          // Si aucune réservation n'est liée, il s'agit d'un canal admin générique
          'email' => $booking ? 'Canal de réservation' : 'Canal admin',
          'conversation_id' => $conv->id,
          'last_preview' => $preview,
          'last_at' => $lastAt,
          'last_at_sort' => $lastAtSort,
          'last_sender_id' => $last?->sender_id,
        ];
        // Insérer dans la liste active et recoller sur $this->users selon l'onglet courant
        array_unshift($this->usersActive, $entry);
        $this->sortArrayByLastAt($this->usersActive);
        $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
        $this->selectedUser = $entry;
      }
    } else {
      // Fallback: reconstruire à partir du modèle
      $user = User::find($id);
      if ($user) {
        // Si un canal admin existe pour cet utilisateur, forcer la sélection de ce canal plutôt que d'ouvrir une DM
        $conv = $this->findPreferredAdminConversationForUser((int) $user->id);
        if ($conv) {
          $booking = $conv->booking_id ? \App\Models\Booking::find($conv->booking_id) : null;
          $baseUserName = $conv->user?->name ?? $user->name;
          $propertyName = $baseUserName;
          if ($booking && $booking->property && !empty($booking->property->name)) {
            $propertyName = $booking->property->name . ' - ' . $baseUserName;
          }
          $last = Message::where('conversation_id', $conv->id)->latest('created_at')->first();
          $preview = $last?->content ? Str::limit($last->content, 55) : '';
          $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
          $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;
          $entry = [
            'id' => 'admin_channel_' . $conv->id,
            'name' => $propertyName,
            'email' => $booking ? 'Canal de réservation' : 'Canal admin',
            'conversation_id' => $conv->id,
            'last_preview' => $preview,
            'last_at' => $lastAt,
            'last_at_sort' => $lastAtSort,
            'last_sender_id' => $last?->sender_id,
          ];
          // Injecter si absent puis sélectionner
          $exists = collect($this->usersActive)->contains(fn($u) => ($u['id'] ?? null) === $entry['id'])
            || collect($this->usersArchived)->contains(fn($u) => ($u['id'] ?? null) === $entry['id']);
          if (!$exists) {
            array_unshift($this->usersActive, $entry);
            $this->sortArrayByLastAt($this->usersActive);
            $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
          }
          $this->selectedUser = $entry;
          // Remplacer l'id mémorisé par l'identifiant du canal admin
          $id = $entry['id'];
        } else {
          // Aucun canal admin: rester en DM (numérique)
          $this->selectedUser = [
            'id' => (string) $user->id,
            'name' => $user->name,
            'email' => $user->email,
          ];
        }
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

  /**
   * Revenir à la liste (désélectionne la conversation et notifie la page Filament).
   */
  public function backToList(): void
  {
    $this->showChat = false;
    session()->forget('admin_chat.selected');
    // Notifier la page Filament qu'il n'y a plus de sélection
    $this->dispatch('adminChatCleared');
  }

  /**
   * Envoie un message:
   * - Vers le canal admin (redirigé vers l'utilisateur de la conversation)
   * - Vers un pair en direct
   * Diffuse l'événement temps-réel, met à jour l'UI et notifie par email (sans bloquer en cas d'erreur).
   */
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
        $recipient = User::find($targetUserId);
        if ($recipient && !empty($recipient->email)) {
          $recipient->notify(new \App\Notifications\MessageReceivedNotification($message));
        }
      } catch (\Throwable $e) {
        // ne bloque pas l'envoi du message si l'email échoue
      }
    } else {
      // Si la sélection est un ID numérique, préférer router dans un canal admin existant plutôt que créer une DM
      $peerId = (int) ($this->selectedUser['id'] ?? 0);
      $preferred = $peerId > 0 ? $this->findPreferredAdminConversationForUser($peerId) : null;
      if ($preferred) {
        $message = Message::create([
          'conversation_id' => $preferred->id,
          'sender_id' => Auth::id(),
          'receiver_id' => (int) $preferred->user_id,
          'content' => $this->newMessage,
        ]);
        // S'assurer que la sélection et la liste reflètent le canal admin
        $adminId = 'admin_channel_' . $preferred->id;
        // Injecter/mettre à jour l'entrée
        $lastAtSort = $message->created_at ? $message->created_at->getTimestamp() : time();
        $entry = [
          'id' => $adminId,
          'name' => ($preferred->booking && $preferred->booking->property && $preferred->booking->property->name)
            ? ($preferred->booking->property->name . ' - ' . ($preferred->user?->name ?? 'Utilisateur'))
            : ($preferred->user?->name ?? 'Utilisateur'),
          'email' => $preferred->booking ? 'Canal de réservation' : 'Canal admin',
          'conversation_id' => $preferred->id,
          'last_preview' => Str::limit($message->content, 55),
          'last_at' => $message->created_at ? $message->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '',
          'last_at_sort' => $lastAtSort,
          'last_sender_id' => $message->sender_id,
        ];
        $existsIdx = null;
        foreach ($this->usersActive as $idx => $u) {
          if (($u['id'] ?? null) === $adminId) {
            $existsIdx = $idx;
            break;
          }
        }
        if ($existsIdx === null) {
          array_unshift($this->usersActive, $entry);
        } else {
          $this->usersActive[$existsIdx] = array_merge($this->usersActive[$existsIdx], $entry);
        }
        $this->sortArrayByLastAt($this->usersActive);
        $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
        $this->selectedUser = $entry;
        // Recharger les messages de ce canal pour éviter un mélange avec d'anciennes DMs
        $this->messages = Message::where('conversation_id', $preferred->id)
          ->orderBy('created_at')
          ->get();
      } else {
        // Aucun canal admin: fallback DM direct
        $message = Message::create([
          'sender_id' => Auth::id(),
          'receiver_id' => $peerId,
          'content' => $this->newMessage,
        ]);
        // Notification email destinataire direct
        try {
          $recipient = User::find($message->receiver_id);
          if ($recipient && !empty($recipient->email)) {
            $recipient->notify(new \App\Notifications\MessageReceivedNotification($message));
          }
        } catch (\Throwable $e) {
          // ignorer les erreurs d'email
        }
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

  /**
   * Retourne le canal admin préféré pour un utilisateur: d'abord le canal générique (booking_id NULL),
   * sinon le plus récent des canaux admin existants pour cet utilisateur.
   */
  private function findPreferredAdminConversationForUser(int $userId): ?Conversation
  {
    try {
      $generic = Conversation::where('is_admin_channel', true)
        ->where('user_id', $userId)
        ->whereNull('booking_id')
        ->latest('created_at')
        ->first();
      if ($generic) {
        return $generic;
      }
      return Conversation::where('is_admin_channel', true)
        ->where('user_id', $userId)
        ->latest('created_at')
        ->first();
    } catch (\Throwable $e) {
      return null;
    }
  }

  /**
   * Déclenché à chaque changement de l'input message: diffuse un événement "UserTyping"
   * vers la cible adéquate (utilisateur lié au canal admin ou pair en direct).
   */
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

  /**
   * Déclare les listeners Livewire (canaux privés Echo) utilisés par ce composant.
   */
  public function getListeners()
  {
    return [
      "echo-private:chat.{$this->loginID},MessageSent" => "newChatMessageNotification"
    ];
  }

  /**
   * Handler de réception d'un message temps-réel:
   * - Met à jour la vignette dans la liste (aperçu/date/ordre)
   * - Ajoute le message dans le thread ouvert si concerné et met à jour lastSeen/metadata
   */
  public function newChatMessageNotification($message)
  {
    $messageObj = Message::find($message['id'] ?? 0);
    if ($messageObj) {
      $this->refreshListPreviewForIncoming($message, $messageObj);
    }

    if (!$this->selectedUser) {
      return; // aucune conversation ouverte: la liste a déjà été mise à jour
    }

    if ($this->isAdminChannelOpen()) {
      $this->maybeAppendIfSameAdminChannel($message, $messageObj);
      return;
    }

    $this->maybeAppendIfDirectPeer($message, $messageObj);
  }

  // --- Helpers d'extraction ---
  /**
   * Met à jour la vignette d'une conversation (aperçu, date, ordre) suite à un message entrant.
   * Utilise conversation_id pour canaux admin, sinon déduit le pair (direct).
   */
  private function refreshListPreviewForIncoming(array $payload, Message $messageObj): void
  {
    if (!empty($payload['conversation_id'])) {
      $this->bumpConversationMeta('admin_channel_' . $payload['conversation_id'], $messageObj);
      return;
    }
    $peerId = (string) (((int) ($payload['sender_id'] ?? 0) === (int) Auth::id()) ? ($payload['receiver_id'] ?? '') : ($payload['sender_id'] ?? ''));
    if ($peerId !== '') {
      $this->bumpConversationMeta($peerId, $messageObj);
    }
  }

  /**
   * Indique si la conversation actuellement ouverte est un canal admin.
   */
  private function isAdminChannelOpen(): bool
  {
    return $this->selectedUser && str_starts_with($this->selectedUser['id'], 'admin_channel_');
  }

  /**
   * Si le message reçu appartient au canal admin ouvert, l'ajoute au fil et met à jour lastSeen + metadata.
   */
  private function maybeAppendIfSameAdminChannel(array $payload, ?Message $messageObj): void
  {
    if (!$messageObj) {
      return;
    }
    $openId = $this->selectedUser['conversation_id'] ?? null;
    if (($payload['conversation_id'] ?? null) == $openId) {
      $this->messages->push($messageObj);
      $key = $this->selectedUser['id'];
      $this->lastSeen[$key] = max($this->lastSeen[$key] ?? 0, $messageObj->created_at?->getTimestamp() ?? time());
      $this->computeConversationMeta($this->lastSeen[$key] ?? 0);
    }
  }

  /**
   * Si le message reçu vient du pair correspondant à la discussion directe ouverte,
   * l'ajoute au fil et met à jour lastSeen + metadata.
   */
  private function maybeAppendIfDirectPeer(array $payload, ?Message $messageObj): void
  {
    if (!$messageObj) {
      return;
    }
    $openPeerId = (string) ($this->selectedUser['id'] ?? '');
    if ((string) ($payload['sender_id'] ?? '') === $openPeerId) {
      $this->messages->push($messageObj);
      $this->lastSeen[$openPeerId] = max($this->lastSeen[$openPeerId] ?? 0, $messageObj->created_at?->getTimestamp() ?? time());
      $this->computeConversationMeta($this->lastSeen[$openPeerId] ?? 0);
    }
  }

  #[On('openConversation')]
  /**
   * Ouvre une conversation via événement Livewire (utilisé par la page Filament pour sélectionner un canal).
   */
  public function openConversation($id): void
  {
    if ($id === null || $id === '') {
      return;
    }
    $this->selectUser((string) $id);
  }

  #[On('deleteCurrentConversation')]
  /**
   * Supprime la conversation courante (canal admin ou direct), nettoie les listes et réinitialise l'état.
   */
  public function deleteCurrentConversation(): void
  {
    if (!$this->selectedUser) {
      return;
    }

    $entry = $this->selectedUser;
    $this->deleteEntry($entry);
    $this->cleanListsAfterRemoval($entry['id']);
    $this->resetSelectionAfterDelete();
  }

  #[On('bulkDeleteConversations')]
  /**
   * Supprime en lot des conversations (canaux admin ou directs),
   * nettoie les listes et conserve la liste affichée.
   */
  public function bulkDeleteConversations(array $ids): void
  {
    foreach ($ids as $rawId) {
      $id = (string) $rawId;
      $this->deleteById($id);
      $this->cleanListsAfterRemoval($id);
      if ($this->selectedUser && ($this->selectedUser['id'] ?? null) === $id) {
        $this->resetSelectionAfterDelete(false);
      }
    }

    $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
    $this->dispatch('adminChatCleared');
  }

  // --- Helpers de suppression et nettoyage ---
  /**
   * Supprime une entrée donnée en s'appuyant sur son identifiant et conversation_id si présent.
   */
  private function deleteEntry(array $entry): void
  {
    $id = (string) ($entry['id'] ?? '');
    $this->deleteById($id, $entry['conversation_id'] ?? null);
  }

  /**
   * Supprime une conversation par identifiant:
   * - admin_channel_* => suppression des messages puis de la Conversation
   * - identifiant numérique (direct) => suppression des messages bilatéraux
   */
  private function deleteById(string $id, ?int $conversationId = null): void
  {
    if (str_starts_with($id, 'admin_channel_')) {
      $convId = $conversationId ?? (int) str_replace('admin_channel_', '', $id);
      if ($convId > 0) {
        \App\Models\Message::where('conversation_id', $convId)->delete();
        if ($conv = \App\Models\Conversation::find($convId)) {
          $conv->delete();
        }
      }
      return;
    }
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

  /**
   * Retire l'entrée supprimée des listes actives/archivées et réindexe.
   */
  private function cleanListsAfterRemoval(string $id): void
  {
    $this->usersActive = array_values(array_filter($this->usersActive, fn($u) => ($u['id'] ?? null) !== $id));
    $this->usersArchived = array_values(array_filter($this->usersArchived, fn($u) => ($u['id'] ?? null) !== $id));
  }

  /**
   * Réinitialise la sélection et l'affichage après suppression,
   * et émet l'événement 'adminChatCleared' si demandé.
   */
  private function resetSelectionAfterDelete(bool $emitCleared = true): void
  {
    $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
    $this->selectedUser = null;
    $this->messages = collect();
    $this->showChat = false;
    session()->forget('admin_chat.selected');
    if ($emitCleared) {
      $this->dispatch('adminChatCleared');
    }
  }

  /**
   * Met à jour les métadonnées (aperçu, date, ordre, dernier expéditeur) pour une conversation donnée,
   * dans la liste visible puis dans les listes source (actives/archivées), puis retrie.
   */
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
  /**
   * Calcule les métadonnées d'affichage du fil ouvert:
   * - premier message entrant non lu et le compteur
   * - marquage heuristique de "vu" pour le dernier message sortant
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

  /**
   * Construit la liste des pairs pour discussions directes, avec leurs métadonnées de dernier message.
   */
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

  /**
   * Construit la liste des canaux admin (avec archivage basé sur la fin de réservation),
   * et le nom d'affichage "Résidence - Prénom Nom" si applicable.
   */
  private function buildAdminChannelItems(): array
  {
    $adminChannels = \App\Models\Conversation::where('is_admin_channel', true)
      ->orderByDesc('created_at')
      ->get();

    $items = [];
    foreach ($adminChannels as $adminChannel) {
      $booking = $adminChannel->booking_id ? \App\Models\Booking::find($adminChannel->booking_id) : null;
      // Nom côté admin: si réservation -> "Résidence - Prénom Nom", sinon nom de l'utilisateur
      $baseUserName = $adminChannel->user?->name ?? 'Utilisateur';
      $adminDisplayName = $baseUserName;
      if ($booking && $booking->property && !empty($booking->property->name)) {
        $adminDisplayName = $booking->property->name . ' - ' . $baseUserName;
      }
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
        'name' => $adminDisplayName,
        // Afficher "Canal de réservation" si une réservation est rattachée, sinon "Canal admin"
        'email' => $booking ? 'Canal de réservation' : 'Canal admin',
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

  /**
   * Trie un tableau d'entrées par récence (last_at_sort décroissant).
   */
  private function sortArrayByLastAt(array &$arr): void
  {
    usort($arr, function ($a, $b) {
      return ($b['last_at_sort'] ?? 0) <=> ($a['last_at_sort'] ?? 0);
    });
  }

  /**
   * Change l'onglet actif (actives/archivées) et bascule la liste affichée.
   */
  public function switchTab(string $tab): void
  {
    $tab = in_array($tab, ['active', 'archived'], true) ? $tab : 'active';
    $this->activeTab = $tab;
    $this->users = $this->activeTab === 'active' ? $this->usersActive : $this->usersArchived;
    session(['admin_chat.tab' => $this->activeTab]);
  }

  /**
   * Calcule l'ID du premier message entrant non lu et le compteur de non-lus à partir d'un seuil de seen.
   */
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

  /**
   * Déduit une heuristique de "vu" pour un message sortant: si une réponse suit, on considère le précédent comme vu.
   * Désactivé pour les canaux admin.
   */
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

  /**
   * Rendu Livewire (vue admin-chat-box).
   */
  public function render()
  {
    return view('livewire.admin-chat-box');
  }
}
