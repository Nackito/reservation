<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\Booking;
use App\Models\Conversation;
use App\Models\User;
use App\Events\MessageSent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
  // Réservation liée à la conversation admin sélectionnée (si applicable)
  public $currentBooking = null;

  /**
   * Initialise l'état du composant utilisateur:
   * - Construit la liste (canaux admin + discussions directes)
   * - Trie par récence
   * - Réinitialise sélection/messages, et remet à zéro le compteur global de la navbar
   */
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
    // Si un conversation_id est passé en query, ouvrir directement ce canal admin
    try {
      $cid = request()->query('conversation_id');
      if ($cid) {
        $targetId = 'admin_channel_' . (int) $cid;
        $item = collect($this->users)->firstWhere('id', $targetId);
        if ($item) {
          $this->selectedUser = $item;
          $this->loadMessages();
          $this->showChat = true;
          $this->lastSeen[$targetId] = time();
          $this->dispatch('focusMessageInput');
          $this->dispatch('scrollToBottom');
        }
      }
    } catch (\Throwable $e) {
      // ignorer toute erreur de requête
    }
    // Quand on arrive sur la page chat, on peut remettre le compteur global à zéro
    $this->dispatch('resetNavChatUnseen');
  }

  /**
   * Construit la liste agrégée utilisateur: canaux admin + discussions directes.
   */
  private function buildUserAndChannelItems(): array
  {
    $direct = $this->buildDirectItems();
    $admin = $this->buildAdminItems();
    return array_merge($admin, $direct);
  }

  /**
   * Construit la liste des discussions directes (pairs) avec leurs métadonnées de dernier message.
   */
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

  /**
   * Construit la liste des canaux admin pour l'utilisateur courant:
   * - Filtre les canaux expirés 2 jours après la fin de réservation
   * - Nom d'affichage: résidence si réservation, sinon "Afridayz"
   */
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
      if ($this->isChannelExpiredForUser($booking)) {
        continue;
      }

      $displayName = $this->userDisplayNameForAdminChannel($booking);
      [$preview, $lastAt, $lastAtSort, $lastSenderId] = $this->lastMessageMetaForChannel($channel->id);

      $items[] = [
        'id' => 'admin_channel_' . $channel->id,
        'name' => $displayName,
        // Sous-libellé côté utilisateur: "Afridayz" quand pas de réservation, sinon "Canal de réservation"
        'email' => $booking ? 'Canal de réservation' : 'Afridayz',
        'conversation_id' => $channel->id,
        'booking_id' => $channel->booking_id,
        'last_preview' => $preview,
        'last_at' => $lastAt,
        'last_at_sort' => $lastAtSort,
        'last_sender_id' => $lastSenderId,
      ];
    }
    return $items;
  }

  // --- Helpers buildAdminItems ---
  private function isChannelExpiredForUser(?\App\Models\Booking $booking): bool
  {
    if (!$booking || !$booking->end_date) {
      return false;
    }
    $end = $booking->end_date instanceof \Carbon\Carbon ? $booking->end_date->copy() : \Carbon\Carbon::parse($booking->end_date);
    $cutoff = $end->endOfDay()->addDays(2);
    return \Carbon\Carbon::now()->greaterThan($cutoff);
  }

  private function userDisplayNameForAdminChannel(?\App\Models\Booking $booking): string
  {
    if ($booking && $booking->property && !empty($booking->property->name)) {
      return $booking->property->name;
    }
    return 'Afridayz';
  }

  /**
   * @return array{0:string,1:string,2:int,3:int|null} [preview, lastAt, lastAtSort, lastSenderId]
   */
  private function lastMessageMetaForChannel(int $channelId): array
  {
    $last = Message::where('conversation_id', $channelId)->latest('created_at')->first();
    $preview = $last?->content ? \Illuminate\Support\Str::limit($last->content, 55) : '';
    $lastAt = $last?->created_at ? $last->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
    $lastAtSort = $last?->created_at ? $last->created_at->getTimestamp() : 0;
    $lastSenderId = $last?->sender_id;
    return [$preview, $lastAt, $lastAtSort, $lastSenderId];
  }

  /**
   * Charge les messages pour l'entrée sélectionnée: canal admin (par conversation) ou direct (pair).
   */
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
        ->get()
        // Masquer côté utilisateur le message de récapitulatif initial, la carte le remplace
        ->reject(function ($m) {
          return \Illuminate\Support\Str::startsWith((string) ($m->content ?? ''), 'Nouvelle demande de réservation');
        });
      // Charger la réservation liée pour affichage de la carte
      $conv = $conversationId ? \App\Models\Conversation::find($conversationId) : null;
      $this->currentBooking = ($conv && $conv->booking_id) ? Booking::with('property.images')->find($conv->booking_id) : null;
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

  /**
   * Sélectionne une entrée, charge ses messages, met à jour lastSeen et prépare l'UI (focus + scroll + reset badge navbar).
   */
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
    // Demander au client de scroller tout en bas après le rendu
    $this->dispatch('scrollToBottom');
    // Ouvrir une conversation signifie qu'on consulte: remettre le badge global à zéro
    $this->dispatch('resetNavChatUnseen');
  }

  /**
   * Annule la réservation liée au canal admin sélectionné (ou à l'id fourni) et notifie le canal.
   */
  public function cancelBookingFromChat($conversationId)
  {
    $conv = \App\Models\Conversation::find((int) $conversationId);
    if (!$conv || !$conv->is_admin_channel || (int) $conv->user_id !== (int) Auth::id()) {
      return; // sécurité
    }
    if (!$conv->booking_id) {
      return;
    }
    $booking = Booking::find($conv->booking_id);
    if (!$booking || (int) $booking->user_id !== (int) Auth::id()) {
      return;
    }
    if ($booking->status === 'canceled') {
      return;
    }
    $booking->status = 'canceled';
    $booking->save();
    // Conserver dans l'état pour rafraîchir la carte
    $this->currentBooking = $booking->fresh('property.images');

    // Envoyer un message d'information dans le canal
    $msg = Message::create([
      'conversation_id' => $conv->id,
      'sender_id' => Auth::id(),
      // receiver admin-id placeholder (5), utilisé dans le flux existant
      'receiver_id' => 5,
      'content' => "⚠️ L'utilisateur a annulé sa réservation.",
    ]);
    try {
      broadcast(new \App\Events\MessageSent($msg));
    } catch (\Throwable $e) {
      // ignorer les erreurs de diffusion
    }
    // Mettre à jour la liste et l'aperçu
    $this->bumpConversationMeta('admin_channel_' . $conv->id, $msg);
    // Recharger les messages pour afficher celui d'annulation
    $this->loadMessages();
    $this->dispatch('scrollToBottom');
  }

  /**
   * Revenir à la liste (désélection et masquage du fil).
   */
  public function backToList(): void
  {
    $this->showChat = false;
  }

  /**
   * Envoie un message (canal admin vers support ou direct vers pair), notifie par email (throttle),
   * diffuse en temps réel, met à jour la liste et scroll au bas.
   */
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

    // Notifier le destinataire par email (si disponible) avec un anti-spam simple (3 min)
    try {
      $recipient = User::find($message->receiver_id);
      if ($recipient && !empty($recipient->email)) {
        $throttleKey = 'mail_notify:' . $recipient->id . ':' . ($message->sender_id ?? '');
        if (Cache::add($throttleKey, time(), 180)) {
          $recipient->notify(new \App\Notifications\MessageReceivedNotification($message));
        }
      }
    } catch (\Throwable $e) {
      // On ignore silencieusement toute erreur d'envoi d'email pour ne pas bloquer le chat
    }

    $this->messages = $this->messages instanceof \Illuminate\Support\Collection ? $this->messages : collect($this->messages);
    $this->messages->push($message);

    // Bump aperçu/date de la conversation
    if ($this->selectedUser) {
      $this->bumpConversationMeta($this->selectedUser['id'], $message);
      $this->lastSeen[$this->selectedUser['id']] = time();
    }

    $this->newMessage = '';
    // Demander explicitement au client de vider le champ (sécurise le reset même si l'input est focalisé)
    $this->dispatch('clearMessageInput');
    broadcast(new MessageSent($message));
    $this->dispatch('focusMessageInput');
    // Assurer le scroll en bas après l'ajout
    $this->dispatch('scrollToBottom');
  }

  /**
   * Diffuse un événement "UserTyping" vers le destinataire approprié à chaque frappe.
   */
  public function updatedNewMessage($value)
  {
    if ($this->selectedUser) {
      // Broadcast typing via event to the receiver's private channel (no whisper auth issues)
      $receiverId = str_starts_with($this->selectedUser['id'], 'admin_channel_')
        ? 5
        : (int) $this->selectedUser['id'];
      event(new \App\Events\UserTyping(Auth::id(), $receiverId, Auth::user()->name));
    }
  }

  /**
   * Déclare les listeners Livewire pour la réception d'événements temps réel.
   */
  public function getListeners()
  {
    return [
      "echo-private:chat.{$this->loginID},MessageSent" => 'newChatMessageNotification',
    ];
  }

  /**
   * Traitement d'un message entrant:
   * - Mise à jour des vignettes de liste (aperçu/date/ordre)
   * - Ajout dans le fil ouvert si c'est la conversation courante (admin ou direct)
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
   * Met à jour la vignette correspondante dans la liste suite à un message entrant.
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
   * Indique si la conversation ouverte est un canal admin.
   */
  private function isAdminChannelOpen(): bool
  {
    return $this->selectedUser && str_starts_with($this->selectedUser['id'], 'admin_channel_');
  }

  /**
   * Si le message appartient au canal admin ouvert, l'ajoute au fil et met à jour lastSeen.
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
    }
  }

  /**
   * Si le message provient du pair de la discussion directe ouverte, l'ajoute au fil et met à jour lastSeen.
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
    }
  }

  /**
   * Met à jour l'aperçu, la date, l'ordre et le dernier expéditeur d'une entrée, puis retrie la liste.
   */
  private function bumpConversationMeta(string $id, Message $message): void
  {
    foreach ($this->users as &$u) {
      if ($u['id'] === $id) {
        $u['last_preview'] = \Illuminate\Support\Str::limit($message->content, 55);
        $u['last_at'] = $message->created_at ? $message->created_at->locale('fr')->isoFormat(self::DATE_BADGE_FORMAT) : '';
        $u['last_at_sort'] = $message->created_at ? $message->created_at->getTimestamp() : time();
        $u['last_sender_id'] = $message->sender_id;
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

  /**
   * Rendu Livewire de la vue `livewire.chat-box`.
   */
  public function render()
  {
    return view('livewire.chat-box');
  }
}
