<div>
  <div class="flex h-[550px] text-sm border rounded-xl shadow overflow-hidden bg-white">
    <!-- Left User list -->
    <div class="w-1/4 border-r bg-gray-50">
      <div class="p-4 font-bold text-gray-700 border-b">Users</div>
      <div class="divide-y">
        @foreach ($users as $user)
        <div wire:click="selectUser('{{ $user->id }}')"
          class="p-3 cursor-pointer hover:bg-blue-100 transition
            {{ $selectedUser->id == $user->id ? 'bg-blue-50 font-semibold' : '' }}">
          <div class="text-gray-800">{{ $user->name }}</div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Right Chat box -->
    <div class="w-3/4 flex flex-col">

      <!-- Chat Header -->
      <div class="p-4 border-b bg-gray-50">
        @if($selectedUser)
        <div class="text-lg font-semibold text-gray-800">{{ $selectedUser->name }}</div>
        @else
        <div class="text-lg font-semibold text-gray-800 text-center">Aucune conversation</div>
        @endif
      </div>

      <!-- Chat Messages -->
      <div class="flex-1 overflow-y-auto p-4 bg-gray-50 space-y-2">
        @foreach ($messages as $message)
        <div class="flex {{ $message->sender_id === auth()->id() ? 'justify-end' : 'justify-start' }}">
          <div>
            <div class="max-w-xs px-4 py-2 rounded-2xl shadow {{ $message->sender_id === auth()->id() ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-800' }}">
              {{ $message->content }}
            </div>
            <div class="text-xs text-gray-400 mt-1 text-right">
              {{ $message->created_at ? $message->created_at->format('d/m/Y H:i') : '' }}
            </div>
          </div>
        </div>
        @endforeach
      </div>

      <div id="typing-indicator" class="px-4 pb-1 text-xs text-gray-400 italic"></div>

      <!-- Chat Input -->
      <form wire:submit="submit" class="p-4 border-t bg-white flex items-center gap-2">
        <input
          wire:model.live="newMessage"
          type="text"
          class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          placeholder="Type your message..." />
        <button type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-sm text-white rounded-full px-4 py-2">
          Send
        </button>
      </form>
    </div>
  </div>

  <script>
    document.addEventListener('livewire:initialized', () => {
      Livewire.on('userTyping', (event) => {
        console.log(event);
        window.Echo.private(`chat.${event.selectedUserID}`)
          .whisper('typing', {
            userID: event.userID,
            userName: event.userName
          });
      });

      window.Echo.private(`chat.{{ $loginID }}`)
        .listenForWhisper('typing', (event) => {
          var t = document.getElementById('typing-indicator');
          t.innerText = `${event.userName} is typing...`;

          setTimeout(() => {
            t.innerText = '';
          }, 2000);
        });
    });
  </script>