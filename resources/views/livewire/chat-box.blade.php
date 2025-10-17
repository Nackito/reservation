<div>
  <div class="space-y-4 text-sm">
    @if ($showChat)
    <!-- Chat mono-colonne -->
    <div class="border rounded-xl shadow overflow-hidden bg-white dark:bg-gray-900 dark:border-gray-800">
      <!-- Header -->
      <div class="p-4 border-b bg-white dark:bg-gray-900 dark:border-gray-800 sticky top-0 z-10">
        <div class="flex items-center gap-3">
          <button type="button" wire:click="backToList" class="inline-flex items-center justify-center h-9 w-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300" aria-label="Retour">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
          </button>
          <div class="min-w-0">
            <div class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">{{ data_get($selectedUser, 'name', 'Sélectionnez une conversation') }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ data_get($selectedUser, 'email', '') }}</div>
          </div>
          <div class="ml-auto">
            <div id="typing-indicator" class="text-xs text-gray-400 dark:text-gray-500 italic"></div>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <div id="messages" class="flex-1 max-h-[60vh] overflow-y-auto p-4 bg-gray-50 dark:bg-gray-950 space-y-3">
        @if($selectedUser && str_starts_with($selectedUser['id'] ?? '', 'admin_channel_'))
        @php
        $bk = $this->currentBooking ?? null;
        $isPending = $bk && ($bk->status === 'pending');
        $isAccepted = $bk && ($bk->status === 'accepted');
        $isCanceled = $bk && ($bk->status === 'canceled');
        $badgeClass = $isPending ? 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-200 dark:border-yellow-800'
        : ($isAccepted ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-200 dark:border-green-800'
        : 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-200 dark:border-red-800');
        $statusLabel = $isPending ? 'En attente de confirmation' : ($isAccepted ? 'Confirmée' : 'Annulée');
        @endphp
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm">
          <div class="flex items-start gap-3">
            <div class="shrink-0 w-12 h-12 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700">
              @if($bk && $bk->property && $bk->property->images && $bk->property->images->isNotEmpty())
              <img src="{{ asset('storage/' . $bk->property->images->first()->image_path) }}" alt="" class="w-full h-full object-cover" />
              @else
              <div class="w-full h-full flex items-center justify-center text-gray-400">🏨</div>
              @endif
            </div>
            <div class="min-w-0 flex-1">
              <div class="flex flex-wrap items-center gap-2">
                <div class="text-base font-semibold text-gray-900 dark:text-gray-100">
                  {{ $bk && $bk->property ? $bk->property->name : 'Réservation' }}
                </div>
                @if($bk)
                <span class="inline-block text-[11px] px-2 py-0.5 rounded border {{ $badgeClass }}">{{ $statusLabel }}</span>
                @endif
              </div>
              @if($bk)
              <div class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                @php
                try {
                $ciFr = \Illuminate\Support\Str::title(\Carbon\Carbon::parse($bk->start_date)->locale('fr')->translatedFormat('l d F Y'));
                $coFr = \Illuminate\Support\Str::title(\Carbon\Carbon::parse($bk->end_date)->locale('fr')->translatedFormat('l d F Y'));
                } catch (\Throwable $e) { $ciFr = $bk->start_date; $coFr = $bk->end_date; }
                @endphp
                <span class="font-medium">Séjour:</span> {{ $ciFr }} → {{ $coFr }}
                @if(!is_null($bk->total_price))
                <span class="ml-3 font-medium">Total:</span> {{ number_format($bk->total_price, 2) }} XOF
                @endif
              </div>
              @endif

              <div class="mt-3 flex items-center gap-2">
                @if($bk && $bk->status !== 'canceled')
                <button type="button" class="px-3 py-1.5 rounded bg-red-600 hover:bg-red-700 text-white text-sm"
                  wire:click="cancelBookingFromChat({{ $selectedUser['conversation_id'] ?? 'null' }})">
                  Annuler
                </button>
                @endif
              </div>
            </div>
          </div>
        </div>
        @endif
        @php $currentDate = null; @endphp
        @forelse ($messages as $message)
        @php
        $dateStr = $message->created_at ? $message->created_at->format('d/m/Y') : '';
        $isMine = $message->sender_id === auth()->id();
        @endphp

        @if ($dateStr !== $currentDate)
        @php $currentDate = $dateStr; @endphp
        <div class="flex items-center justify-center my-2">
          <span class="px-3 py-1 text-[11px] rounded-full bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $currentDate }}</span>
        </div>
        @endif

        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
          <div class="max-w-[70%]">
            <div class="px-4 py-2 rounded-2xl shadow {{ $isMine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-white text-gray-800 border rounded-bl-sm dark:bg-gray-800 dark:text-gray-100 dark:border-gray-700' }}">
              {!! $message->content_html !!}
            </div>
            <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 whitespace-nowrap {{ $isMine ? 'text-right' : 'text-left' }}">
              {{ $message->created_at ? $message->created_at->format('H:i') : '' }}
            </div>
          </div>
        </div>
        @empty
        <div class="h-40 flex items-center justify-center text-gray-400 dark:text-gray-500">
          Aucune conversation sélectionnée.
        </div>
        @endforelse
      </div>

      <!-- Input -->
      <form wire:submit.prevent="submit" class="p-3 border-t bg-white dark:bg-gray-900 dark:border-gray-800 flex items-center gap-2">
        <input
          id="message-input"
          wire:model.live="newMessage"
          type="text"
          class="flex-1 border border-gray-300 dark:border-gray-700 rounded-full px-4 py-2 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 caret-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-600"
          placeholder="Écrire un message..."
          autocomplete="off" />
        <button type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-sm text-white rounded-full px-4 py-2">
          Envoyer
        </button>
      </form>
    </div>
    @else

    <!-- Liste des conversations (mono-colonne) -->
    <div class="border rounded-xl shadow overflow-hidden bg-white dark:bg-gray-900 dark:border-gray-800">
      <div class="p-4 border-b dark:border-gray-800 flex items-center justify-between">
        <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Conversations</div>
        <div class="text-xs text-gray-500 dark:text-gray-400">{{ is_countable($users) ? count($users) : 0 }} au total</div>
      </div>
      <div class="bg-gray-50 dark:bg-gray-950">
        <div class="p-4 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Demandes de réservation</div>
        <div class="divide-y">
          @foreach ($users as $user)
          @if (str_starts_with($user['id'], 'admin_channel_'))
          @php
          $isActive = isset($selectedUser['id']) && $selectedUser['id'] === $user['id'];
          $initial = trim($user['name']) !== '' ? strtoupper(mb_substr($user['name'], 0, 1)) : '?';
          @endphp
          <button type="button" wire:key="u-{{ $user['id'] }}" wire:click.prevent="selectUser('{{ $user['id'] }}')"
            class="w-full text-left p-3 flex items-center gap-3 transition {{ $isActive ? 'bg-blue-50 dark:bg-gray-800 ring-1 ring-inset ring-blue-200 dark:ring-gray-700' : 'hover:bg-blue-50 dark:hover:bg-gray-800' }}">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-600 text-white text-sm font-semibold shrink-0">{{ $initial }}</span>
            <div class="flex-1 min-w-0 overflow-hidden">
              <div class="flex items-baseline gap-2">
                <span class="truncate min-w-0 text-gray-900 dark:text-gray-100 font-medium {{ $isActive ? 'font-semibold' : '' }}">{{ $user['name'] }}</span>
                <span class="ml-auto shrink-0 whitespace-nowrap text-[11px] text-gray-500 dark:text-gray-400">{{ $user['last_at'] ?? '' }}</span>
              </div>
              <div class="truncate text-gray-500 dark:text-gray-400 text-xs">{{ $user['last_preview'] ?? $user['email'] }}</div>
            </div>
            <span class="ml-2 flex items-center gap-2 shrink-0">
              @php
              $ls = $lastSeen[$user['id']] ?? 0;
              $hasUnread = isset($user['last_at_sort'], $user['last_sender_id']) && $user['last_sender_id'] !== auth()->id() && $user['last_at_sort'] > $ls;
              @endphp
              @if ($hasUnread)
              <span class="h-2.5 w-2.5 rounded-full bg-blue-500 inline-block"></span>
              @endif
            </span>
          </button>
          @endif
          @endforeach
        </div>

        <div class="p-4 text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Discussions directes</div>
        <div class="divide-y">
          @foreach ($users as $user)
          @if (!str_starts_with($user['id'], 'admin_channel_'))
          @php
          $isActive = isset($selectedUser['id']) && $selectedUser['id'] === $user['id'];
          $initial = trim($user['name']) !== '' ? strtoupper(mb_substr($user['name'], 0, 1)) : '?';
          @endphp
          <button type="button" wire:key="u-{{ $user['id'] }}" wire:click.prevent="selectUser('{{ $user['id'] }}')"
            class="w-full text-left p-3 flex items-center gap-3 transition {{ $isActive ? 'bg-blue-50 dark:bg-gray-800 ring-1 ring-inset ring-blue-200 dark:ring-gray-700' : 'hover:bg-blue-50 dark:hover:bg-gray-800' }}">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-gray-900 text-white text-sm font-semibold shrink-0">{{ $initial }}</span>
            <div class="flex-1 min-w-0 overflow-hidden">
              <div class="flex items-baseline gap-2">
                <span class="truncate min-w-0 text-gray-900 dark:text-gray-100 font-medium {{ $isActive ? 'font-semibold' : '' }}">{{ $user['name'] }}</span>
                <span class="ml-auto shrink-0 whitespace-nowrap text-[11px] text-gray-500 dark:text-gray-400">{{ $user['last_at'] ?? '' }}</span>
              </div>
              <div class="truncate text-gray-500 dark:text-gray-400 text-xs">{{ $user['last_preview'] ?? $user['email'] }}</div>
            </div>
            <span class="ml-2 flex items-center gap-2 shrink-0">
              @php
              $ls = $lastSeen[$user['id']] ?? 0;
              $hasUnread = isset($user['last_at_sort'], $user['last_sender_id']) && $user['last_sender_id'] !== auth()->id() && $user['last_at_sort'] > $ls;
              @endphp
              @if ($hasUnread)
              <span class="h-2.5 w-2.5 rounded-full bg-blue-500 inline-block"></span>
              @endif
            </span>
          </button>
          @endif
          @endforeach
        </div>
      </div>
    </div>
    @endif
  </div>

  <script>
    document.addEventListener('livewire:init', () => {
      const scrollToBottom = () => {
        const c = document.getElementById('messages');
        if (c) c.scrollTop = c.scrollHeight;
      };
      // Scroll initial au montage
      scrollToBottom();
      if (window.Livewire && typeof window.Livewire.hook === 'function') {
        // Scroll après chaque mise à jour Livewire
        window.Livewire.hook('message.processed', () => scrollToBottom());
      }

      // Evènement explicite en provenance du composant PHP
      Livewire.on('scrollToBottom', () => {
        // Laisser le temps au DOM de se peindre
        setTimeout(scrollToBottom, 0);
      });

      // Indicateur de frappe via event broadcast UserTyping
      if (window.Echo && typeof window.Echo.private === 'function') {
        window.Echo.private(`chat.{{ $loginID }}`)
          .listen('.UserTyping', (event) => {
            const t = document.getElementById('typing-indicator');
            if (!t) return;
            t.innerText = `${event.userName} est en train d'écrire...`;
            setTimeout(() => {
              if (t.innerText.includes("est en train d'écrire")) t.innerText = '';
            }, 2000);
          });
      }

      Livewire.on('focusMessageInput', () => {
        const input = document.getElementById('message-input');
        if (input) input.focus();
      });

      // Vider explicitement le champ après envoi (fallback si le diff DOM ne met pas à jour la valeur focalisée)
      Livewire.on('clearMessageInput', () => {
        const input = document.getElementById('message-input');
        if (input) {
          input.value = '';
          // Émettre un event input pour notifier Livewire si nécessaire
          input.dispatchEvent(new Event('input', {
            bubbles: true
          }));
        }
      });
    });
  </script>
</div>