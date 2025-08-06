<div>
  <div class="flex h-[550px] text-sm border rounded-xl shadow overflow-hidden bg-white">
    <!-- Liste des utilisateurs à gauche -->
    <div class="w-1/4 border-r bg-gray-50">
      <div class="p-4 font-bold text-gray-700 border-b">Utilisateurs</div>
      <div class="divide-y">
        @foreach ($conversations as $conversation)
        <div class="p-3 cursor-pointer hover:bg-blue-100 transition @if($selectedUserId === $conversation->sender_id) bg-blue-200 @endif"
          wire:click="$set('selectedUserId', {{ $conversation->sender_id }})">
          <div class="text-gray-800">{{ $conversation->sender->name }}</div>
          <div class="text-xs text-gray-500">{{ $conversation->sender->email }}</div>
        </div>
        @endforeach
      </div>
    </div>

    <!-- Boîte de chat à droite -->
    <div class="w-3/4 flex flex-col">
      @if($selectedUserId)
      <!-- En-tête du chat -->
      <div class="p-4 border-b bg-gray-50">
        <div class="text-lg font-semibold text-gray-800">
          {{ optional($conversations->firstWhere('sender_id', $selectedUserId))->sender->name ?? '' }}
        </div>
        <div class="text-xs text-gray-500">
          {{ optional($conversations->firstWhere('sender_id', $selectedUserId))->sender->email ?? '' }}
        </div>
      </div>

      <!-- Messages du chat -->
      <div class="flex-1 overflow-y-auto p-4 bg-gray-50 space-y-2">
        @foreach ($messages as $message)
        <div class="flex @if ($message->sender_id === auth()->id()) justify-end @else justify-start @endif">
          <div class="max-w-xs px-4 py-2 rounded-2xl shadow @if ($message->sender_id === auth()->id()) bg-blue-600 text-white @else bg-gray-300 text-gray-800 @endif">
            {{ $message->content }}
          </div>
        </div>
        @endforeach
      </div>

      <!-- Saisie du message -->
      <form wire:submit.prevent="sendMessage" class="p-4 border-t bg-white flex items-center gap-2">
        <input type="text" wire:model.defer="newMessage"
          class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-600"
          placeholder="Écrire un message..." />
        <button type="submit"
          class="bg-blue-600 hover:bg-blue-700 text-sm text-white rounded-full px-4 py-2">
          Envoyer
        </button>
      </form>
      @else
      <div class="flex-1 flex items-center justify-center text-gray-400">
        Sélectionnez un utilisateur pour afficher la conversation.
      </div>
      @endif
    </div>
  </div>