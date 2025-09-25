<div>
  <div class="flex h-[600px] text-sm border rounded-xl shadow overflow-hidden bg-white">
    <!-- Left User list -->
    <div class="w-1/3 border-r bg-gray-50">
      <div class="p-4 text-xs uppercase tracking-wide text-gray-500">Demandes de réservation</div>
      <div class="divide-y">
        @foreach ($users as $user)
        @if (str_starts_with($user['id'], 'admin_channel_'))
        @php
        $isActive = isset($selectedUser['id']) && $selectedUser['id'] === $user['id'];
        $initial = trim($user['name']) !== '' ? strtoupper(mb_substr($user['name'], 0, 1)) : '?';
        @endphp
        <button type="button" wire:key="user-{{ $user['id'] }}" wire:click.prevent="selectUser('{{ $user['id'] }}')"
          class="w-full text-left p-3 flex items-center gap-3 transition {{ $isActive ? 'bg-blue-50 ring-1 ring-inset ring-blue-200' : 'hover:bg-blue-50' }}">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-white text-sm font-semibold">{{ $initial }}</span>
          <span class="flex-1 min-w-0">
            <span class="block truncate text-gray-900 font-medium {{ $isActive ? 'font-semibold' : '' }}">{{ $user['name'] }}</span>
            <span class="block truncate text-gray-500 text-xs">{{ $user['email'] }}</span>
          </span>
        </button>
        @endif
        @endforeach
      </div>
      <div class="p-4 text-xs uppercase tracking-wide text-gray-500">Discussions directes</div>
      <div class="divide-y">
        @foreach ($users as $user)
        @if (!str_starts_with($user['id'], 'admin_channel_'))
        @php
        $isActive = isset($selectedUser['id']) && $selectedUser['id'] === $user['id'];
        $initial = trim($user['name']) !== '' ? strtoupper(mb_substr($user['name'], 0, 1)) : '?';
        @endphp
        <button type="button" wire:key="user-{{ $user['id'] }}" wire:click.prevent="selectUser('{{ $user['id'] }}')"
          class="w-full text-left p-3 flex items-center gap-3 transition {{ $isActive ? 'bg-blue-50 ring-1 ring-inset ring-blue-200' : 'hover:bg-blue-50' }}">
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gray-900 text-white text-sm font-semibold">{{ $initial }}</span>
          <span class="flex-1 min-w-0">
            <span class="block truncate text-gray-900 font-medium {{ $isActive ? 'font-semibold' : '' }}">{{ $user['name'] }}</span>
            <span class="block truncate text-gray-500 text-xs">{{ $user['email'] }}</span>
          </span>
        </button>
        @endif
        @endforeach
      </div>
    </div>

    <!-- Right Chat box -->
    <div class="w-2/3 flex flex-col bg-white">

      <!-- Chat Header -->
      <div class="p-4 border-b bg-white sticky top-0 z-10">
        <div class="flex items-center gap-3">
          @php
          $selInitial = trim(data_get($selectedUser, 'name', '')) !== '' ? strtoupper(mb_substr(data_get($selectedUser, 'name'), 0, 1)) : '?';
          @endphp
          <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-600 text-white text-sm font-semibold">{{ $selInitial }}</span>
          <div class="min-w-0">
            <div class="text-base font-semibold text-gray-900 truncate">{{ data_get($selectedUser, 'name', 'Sélectionnez une conversation') }}</div>
            <div class="text-xs text-gray-500 truncate">{{ data_get($selectedUser, 'email', '') }}</div>
          </div>
          <div class="ml-auto">
            <div id="typing-indicator" class="text-xs text-gray-400 italic"></div>
          </div>
        </div>
      </div>

      <!-- Chat Messages -->
      <div id="messages" class="flex-1 overflow-y-auto p-4 bg-gray-50 space-y-3">
        @php $currentDate = null; @endphp
        @forelse ($messages as $message)
        @php
        $dateStr = $message->created_at ? $message->created_at->format('d/m/Y') : '';
        $isMine = $message->sender_id === auth()->id();
        @endphp

        @if ($dateStr !== $currentDate)
        @php $currentDate = $dateStr; @endphp
        <div class="flex items-center justify-center my-2">
          <span class="px-3 py-1 text-[11px] rounded-full bg-gray-200 text-gray-600">{{ $currentDate }}</span>
        </div>
        @endif

        <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}">
          <div class="max-w-[70%]">
            <div class="px-4 py-2 rounded-2xl shadow {{ $isMine ? 'bg-blue-600 text-white rounded-br-sm' : 'bg-white text-gray-800 border rounded-bl-sm' }}">
              {{ $message->content }}
            </div>
            <div class="text-[11px] text-gray-400 mt-1 {{ $isMine ? 'text-right' : 'text-left' }}">
              {{ $message->created_at ? $message->created_at->format('H:i') : '' }}
            </div>
          </div>
        </div>
        @empty
        <div class="h-full flex items-center justify-center text-gray-400">
          Aucune conversation sélectionnée.
        </div>
        @endforelse
      </div>

      <!-- Chat Input -->
      <form wire:submit.prevent="submit" class="p-3 border-t bg-white flex items-center gap-2">
        <input
          wire:model.live="newMessage"
          type="text"
          class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          placeholder="Écrire un message..." />
        <button type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-sm text-white rounded-full px-4 py-2">
          Envoyer
        </button>
      </form>
    </div>
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

      // Whisper "typing"
      Livewire.on('userTyping', (event) => {
        if (window.Echo && typeof window.Echo.private === 'function') {
          window.Echo.private(`chat.${event.selectedUserID}`)
            .whisper('typing', {
              userID: event.userID,
              userName: event.userName
            });
        }
      });

      // Réception de l'indicateur "typing"
      if (window.Echo && typeof window.Echo.private === 'function') {
        window.Echo.private(`chat.{{ $loginID }}`)
          .listenForWhisper('typing', (event) => {
            const t = document.getElementById('typing-indicator');
            if (!t) return;
            t.innerText = `${event.userName} est en train d'écrire...`;
            // Effacer après 2 secondes
            setTimeout(() => {
              if (t.innerText.includes("est en train d'écrire")) t.innerText = '';
            }, 2000);
          });
      }
    });
  </script>