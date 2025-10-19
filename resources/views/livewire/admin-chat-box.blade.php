<div>
  <div class="space-y-4">
    <!-- Chat panel (full width) shown above the list when a conversation is selected) -->
    @if ($showChat)
    <div class="text-sm border rounded-xl shadow overflow-hidden bg-white dark:bg-gray-900 dark:border-gray-800">
      <!-- Chat Header -->
      <div class="p-4 border-b bg-white dark:bg-gray-900 dark:border-gray-800 sticky top-0 z-10">
        <div class="flex items-center gap-3">
          <button type="button" wire:click="backToList" class="inline-flex items-center justify-center h-9 w-9 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-300" aria-label="Retour">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
            </svg>
          </button>
          <div class="min-w-0">
            <div class="text-base font-semibold text-gray-900 dark:text-gray-100 truncate">{{ data_get($selectedUser, 'name', 'Sélectionnez une conversation') }}</div>
            @php $email = data_get($selectedUser, 'email', ''); $label = data_get($selectedUser, 'channel_label', ''); @endphp
            <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $email !== '' ? $email : $label }}</div>
          </div>

          <div class="ml-auto">
            <div id="typing-indicator" class="text-xs text-gray-400 dark:text-gray-500 italic"></div>
          </div>
        </div>
      </div>

      <!-- Chat Messages -->
      <div id="messages" class="flex-1 max-h-[60vh] overflow-y-auto p-4 bg-gray-50 dark:bg-gray-950 space-y-3">
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

        @if ($firstUnreadMessageId && $unreadCount > 0 && $message->id === $firstUnreadMessageId)
        <div class="flex items-center justify-center my-2">
          <span class="px-3 py-1 text-[11px] rounded-full bg-blue-100 text-blue-700 ring-1 ring-inset ring-blue-200 dark:bg-blue-900/30 dark:text-blue-300 dark:ring-blue-800/60">
            {{ $unreadCount }} {{ $unreadCount > 1 ? 'messages non lus' : 'message non lu' }}
          </span>
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
            @if ($isMine && $lastOutgoingSeenMessageId && $message->id === $lastOutgoingSeenMessageId)
            <div class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 text-right">Vu • {{ $lastOutgoingSeenAt }}</div>
            @endif
          </div>
        </div>
        @empty
        <div class="h-40 flex items-center justify-center text-gray-400 dark:text-gray-500">
          Aucune conversation sélectionnée.
        </div>
        @endforelse
      </div>

      <!-- Chat Input -->
      <form wire:submit.prevent="submit" class="p-3 border-t bg-white dark:bg-gray-900 dark:border-gray-800 flex items-center gap-2">
        <input
          id="message-input"
          wire:model.debounce.300ms="newMessage"
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

    <!-- Conversations list (full width) -->
    <div class="text-sm border rounded-xl shadow overflow-hidden bg-white dark:bg-gray-900 dark:border-gray-800">
      <div class="p-4 border-b">
        <div class="flex items-center justify-between">
          <div class="text-sm font-semibold text-gray-800 dark:text-gray-100">Messages</div>
          <div class="text-xs text-gray-500 dark:text-gray-400">{{ is_countable($users) ? count($users) : 0 }} au total</div>
        </div>
        <!-- Tabs: Actives / Archivées (uniquement côté admin) -->
        <div class="mt-3">
          <div class="inline-flex rounded-lg bg-gray-100 dark:bg-gray-800 p-1">
            <button type="button" wire:click="switchTab('active')"
              class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $activeTab === 'active' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100' }}">
              Actives
            </button>
            <button type="button" wire:click="switchTab('archived')"
              class="px-3 py-1.5 rounded-md text-xs font-medium transition {{ $activeTab === 'archived' ? 'bg-white dark:bg-gray-700 shadow text-gray-900 dark:text-gray-100' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-gray-100' }}">
              Archivées
            </button>
          </div>
        </div>
      </div>
      <div class="bg-gray-50 dark:bg-gray-950">
        @php $total = is_countable($users) ? count($users) : 0; @endphp
        @if ($total > 0)
        <div class="divide-y">
          @foreach ($users as $user)
          @php
          $isActive = isset($selectedUser['id']) && $selectedUser['id'] === $user['id'];
          $initial = trim($user['name']) !== '' ? strtoupper(mb_substr($user['name'], 0, 1)) : '?';
          $isAdminChannel = str_starts_with($user['id'], 'admin_channel_');
          @endphp
          <button type="button" wire:key="user-{{ $user['id'] }}" wire:click.prevent="selectUser('{{ $user['id'] }}')"
            class="w-full text-left p-3 flex items-center gap-3 transition {{ $isActive ? 'bg-blue-50 dark:bg-gray-800 ring-1 ring-inset ring-blue-200 dark:ring-gray-700' : 'hover:bg-blue-50 dark:hover:bg-gray-800' }}">
            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full {{ $isAdminChannel ? 'bg-blue-600' : 'bg-gray-900' }} text-white text-sm font-semibold shrink-0">{{ $initial }}</span>
            <div class="flex-1 min-w-0 overflow-hidden">
              <div class="flex items-baseline gap-2">
                <span class="truncate min-w-0 text-gray-900 dark:text-gray-100 font-medium {{ $isActive ? 'font-semibold' : '' }}">{{ $user['name'] }}</span>
                <span class="ml-auto shrink-0 whitespace-nowrap text-[11px] text-gray-500 dark:text-gray-400">{{ $user['last_at'] ?? '' }}</span>
              </div>
              @php $email = $user['email'] ?? ''; $label = $user['channel_label'] ?? ''; @endphp
              <div class="truncate text-gray-500 dark:text-gray-400 text-xs">{{ ($user['last_preview'] ?? '') !== '' ? $user['last_preview'] : ($email !== '' ? $email : $label) }}</div>
            </div>
            <span class="ml-2 flex items-center gap-2 shrink-0">
              @php
              $lastSeen = $lastSeen[$user['id']] ?? 0;
              $hasUnread = isset($user['last_at_sort'], $user['last_sender_id'])
              && $user['last_sender_id'] !== auth()->id()
              && $user['last_at_sort'] > $lastSeen;
              @endphp
              @if ($hasUnread)
              <span class="h-2.5 w-2.5 rounded-full bg-blue-500 inline-block"></span>
              @endif
            </span>
          </button>
          @endforeach
        </div>
        @else
        <div class="px-6 py-10 sm:px-12 sm:py-16">
          <div class="flex items-center gap-6">
            <div class="shrink-0 w-28 h-28 sm:w-36 sm:h-36 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-14 h-14 text-gray-400 dark:text-gray-500">
                <path d="M7.5 3h9a4.5 4.5 0 014.5 4.5v6a4.5 4.5 0 01-4.5 4.5h-2.379l-2.94 2.94A1.5 1.5 0 019 19.94V18H7.5A4.5 4.5 0 013 13.5v-6A4.5 4.5 0 017.5 3z" />
              </svg>
            </div>
            <div class="min-w-0">
              <div class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100">Aucune conversation pour le moment</div>
              <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Les messages récents apparaîtront ici dès qu’un client vous écrira.</div>
            </div>
          </div>
        </div>
        @endif
      </div>
    </div>
  </div>
  @endif
</div>


<script>
  // Livewire v3: initialisation + auto-scroll + indicateur de frappe
  document.addEventListener('livewire:init', () => {
    const scrollToBottom = () => {
      const c = document.getElementById('messages');
      if (c) c.scrollTop = c.scrollHeight;
    };

    // Scroll initial
    scrollToBottom();

    // Scroll après chaque update Livewire
    if (window.Livewire && typeof window.Livewire.hook === 'function') {
      window.Livewire.hook('message.processed', () => scrollToBottom());
    }

    // Evènement explicite depuis le composant côté PHP
    Livewire.on('scrollToBottom', () => {
      setTimeout(scrollToBottom, 0);
    });

    // Whisper "typing"
    // Évite les whispers sur le canal privé de l'autre (403 auth). Voir notes dans chat-box.
    Livewire.on('userTyping', () => {});

    // Réception de l'indicateur "typing" via event broadcast UserTyping
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

    // Focus input message sur demande du composant
    Livewire.on('focusMessageInput', () => {
      const input = document.getElementById('message-input');
      if (input) input.focus();
    });
  });
</script>

<!-- EOF -->