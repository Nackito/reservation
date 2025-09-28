<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use Livewire\Attributes\On;

class AdminChat extends Page
{
  //protected static ?string $navigationIcon = 'heroicon-o-chat-alt-2';
  protected static ?string $navigationLabel = 'Conversations';
  protected string $view = 'filament.pages.admin-chat';
  public bool $hasSelected = false;

  public function mount(): void
  {
    $this->hasSelected = (bool) Session::get('admin_chat.selected');
  }

  public static function canView(): bool
  {
    // Seulement admin
    return Auth::user()?->email === 'admin1@example.com';
  }

  public static function getNavigationItems(): array
  {
    return [
      \Filament\Navigation\NavigationItem::make()
        ->label('Conversations')
        ->icon('heroicon-o-chat-bubble-left-right')
        ->url(static::getUrl())
        ->isActiveWhen(fn(): bool => request()->routeIs(static::getRouteName())),
    ];
  }

  protected function getHeaderActions(): array
  {
    return [
      Action::make('create')
        ->label('Nouveau Chat')
        ->icon('heroicon-o-plus')
        ->form([
          Select::make('user_id')
            ->label('Utilisateur')
            ->searchable()
            ->options(fn() => User::query()
              ->whereNot('id', Auth::id())
              ->orderBy('name')
              ->pluck('name', 'id'))
            ->required(),
        ])
        ->action(function (array $data) {
          // Créer un canal admin partagé entre employés (visible par tous les employés)
          $conv = \App\Models\Conversation::create([
            'user_id' => (int) $data['user_id'],
            'owner_id' => (int) Auth::id(),
            'booking_id' => null,
            'is_admin_channel' => true,
          ]);

          // Envoyer un premier message pour matérialiser la conversation côté utilisateur
          $message = \App\Models\Message::create([
            'conversation_id' => $conv->id,
            'sender_id' => (int) Auth::id(),
            'receiver_id' => (int) $data['user_id'],
            'content' => 'Bonjour, notre conversation vient de démarrer.',
          ]);
          try {
            broadcast(new \App\Events\MessageSent($message));
          } catch (\Throwable $e) {
            Log::warning('Broadcast MessageSent failed for newly created admin channel', [
              'error' => $e->getMessage(),
            ]);
          }

          // Ouvrir le canal admin fraîchement créé
          $this->dispatch('openConversation', id: 'admin_channel_' . $conv->id)
            ->to(\App\Livewire\AdminChatBox::class);
        }),

      // Supprimer Chat: visible uniquement si une conversation est ouverte
      Action::make('delete')
        ->label('Supprimer Chat')
        ->icon('heroicon-o-trash')
        ->visible(fn() => $this->hasSelected)
        ->requiresConfirmation()
        ->modalHeading('Supprimer la conversation courante ?')
        ->modalDescription('Cette action supprimera définitivement les messages de la conversation sélectionnée.')
        ->modalSubmitActionLabel('Supprimer')
        ->action(fn() => $this->dispatch('deleteCurrentConversation')->to(\App\Livewire\AdminChatBox::class)),

      // Supprimer des conversations (sélection multiple): visible quand AUCUNE conversation n'est ouverte
      Action::make('deleteBulk')
        ->label('Supprimer des conversations')
        ->icon('heroicon-o-trash')
        ->visible(fn() => ! $this->hasSelected)
        ->modalHeading('Supprimer des conversations')
        ->form([
          CheckboxList::make('conversation_ids')
            ->label('Sélectionner les conversations à supprimer')
            ->options(function () {
              $tab = Session::get('admin_chat.tab', 'active');
              $options = [];
              if ($tab === 'archived') {
                // Canaux admin archivés
                foreach (\App\Models\Conversation::where('is_admin_channel', true)->get() as $conv) {
                  $booking = $conv->booking_id ? \App\Models\Booking::find($conv->booking_id) : null;
                  $archived = false;
                  if ($booking && !empty($booking->end_date)) {
                    $grace = (int) config('chat.archive.booking_grace_days', 2);
                    $expiry = \Illuminate\Support\Carbon::parse($booking->end_date)->endOfDay()->addDays($grace);
                    $archived = \Illuminate\Support\Carbon::now()->greaterThan($expiry);
                  }
                  if ($archived) {
                    $label = $booking && $booking->property ? ($booking->property->name . ' (conv ' . $conv->id . ')') : 'Réservation #' . $conv->id;
                    $options['admin_channel_' . $conv->id] = $label;
                  }
                }
                // Directs archivés (inactifs)
                $myId = Auth::id();
                $peerIds = \App\Models\Message::query()
                  ->selectRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as peer', [$myId])
                  ->where(function ($q) use ($myId) {
                    $q->where('sender_id', $myId)->orWhere('receiver_id', $myId);
                  })
                  ->distinct()
                  ->pluck('peer');
                $inactiveDays = (int) config('chat.archive.direct_inactive_days', 14);
                $threshold = \Illuminate\Support\Carbon::now()->subDays($inactiveDays)->getTimestamp();
                foreach (User::whereIn('id', $peerIds)->get() as $u) {
                  $last = \App\Models\Message::query()
                    ->where(function ($q) use ($u, $myId) {
                      $q->where('sender_id', $myId)->where('receiver_id', $u->id);
                    })
                    ->orWhere(function ($q) use ($u, $myId) {
                      $q->where('sender_id', $u->id)->where('receiver_id', $myId);
                    })
                    ->latest('created_at')
                    ->first();
                  $lastTs = $last?->created_at?->getTimestamp() ?? 0;
                  if ($lastTs === 0 || $lastTs < $threshold) {
                    $options[(string) $u->id] = $u->name . ' (direct)';
                  }
                }
              } else {
                // Actives
                foreach (\App\Models\Conversation::where('is_admin_channel', true)->get() as $conv) {
                  $booking = $conv->booking_id ? \App\Models\Booking::find($conv->booking_id) : null;
                  $archived = false;
                  if ($booking && !empty($booking->end_date)) {
                    $grace = (int) config('chat.archive.booking_grace_days', 2);
                    $expiry = \Illuminate\Support\Carbon::parse($booking->end_date)->endOfDay()->addDays($grace);
                    $archived = \Illuminate\Support\Carbon::now()->greaterThan($expiry);
                  }
                  if (!$archived) {
                    $label = $booking && $booking->property ? ($booking->property->name . ' (conv ' . $conv->id . ')') : 'Réservation #' . $conv->id;
                    $options['admin_channel_' . $conv->id] = $label;
                  }
                }
                $myId = Auth::id();
                $peerIds = \App\Models\Message::query()
                  ->selectRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as peer', [$myId])
                  ->where(function ($q) use ($myId) {
                    $q->where('sender_id', $myId)->orWhere('receiver_id', $myId);
                  })
                  ->distinct()
                  ->pluck('peer');
                $inactiveDays = (int) config('chat.archive.direct_inactive_days', 14);
                $threshold = \Illuminate\Support\Carbon::now()->subDays($inactiveDays)->getTimestamp();
                foreach (User::whereIn('id', $peerIds)->orderBy('name')->get() as $u) {
                  $last = \App\Models\Message::query()
                    ->where(function ($q) use ($u, $myId) {
                      $q->where('sender_id', $myId)->where('receiver_id', $u->id);
                    })
                    ->orWhere(function ($q) use ($u, $myId) {
                      $q->where('sender_id', $u->id)->where('receiver_id', $myId);
                    })
                    ->latest('created_at')
                    ->first();
                  $lastTs = $last?->created_at?->getTimestamp() ?? 0;
                  if ($lastTs >= $threshold) {
                    $options[(string) $u->id] = $u->name . ' (direct)';
                  }
                }
              }
              return $options;
            })
            ->bulkToggleable()
            ->columns(1)
            ->required(),
        ])
        ->modalSubmitActionLabel('Supprimer sélection')
        ->action(function (array $data) {
          $ids = (array) ($data['conversation_ids'] ?? []);
          if (count($ids) > 0) {
            $this->dispatch('bulkDeleteConversations', ids: $ids)->to(\App\Livewire\AdminChatBox::class);
          }
        }),
    ];
  }

  #[On('adminChatSelected')]
  public function onAdminChatSelected(): void
  {
    $this->hasSelected = true;
  }

  #[On('adminChatCleared')]
  public function onAdminChatCleared(): void
  {
    $this->hasSelected = false;
  }
}
