<div class="flex flex-col h-full">
  <!-- Header -->
  <div class="p-4 bg-blue-600 text-white font-bold text-lg">
    Boîte de Chat Admin
  </div>

  <!-- Messages -->
  <div class="flex-1 overflow-y-auto p-4 bg-gray-100">
    @foreach ($messages as $message)
    <div class="mb-4 @if ($message->sender_id === auth()->id()) text-right @endif">
      <span class="inline-block px-3 py-2 rounded-lg @if ($message->sender_id === auth()->id()) bg-blue-500 text-white @else bg-gray-300 text-gray-800 @endif">
        {{ $message->content }}
      </span>
      <div class="text-xs text-gray-500 mt-1">
        {{ $message->created_at->format('d/m/Y H:i') }}
      </div>
    </div>
    @endforeach
  </div>

  <!-- Input -->
  <form wire:submit.prevent="sendMessage" class="flex p-4 border-t bg-white items-center gap-2">
    <input
      type="text"
      wire:model.defer="newMessage"
      class="flex-1 border border-gray-300 rounded-full px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
      placeholder="Écrire un message...">
    <button type="submit"
      class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-4 py-2 rounded-full">Envoyer</button>
  </form>
</div>