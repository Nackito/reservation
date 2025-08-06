<div>
  <div class="overflow-y-auto h-80 bg-gray-100 p-4 rounded">
    @foreach ($messages as $message)
    <div class="mb-2 @if ($message->sender_id === auth()->id()) text-right @endif">
      <span class="inline-block px-3 py-2 rounded-lg @if ($message->sender_id === auth()->id()) bg-blue-500 text-white @else bg-gray-300 text-gray-800 @endif">
        {{ $message->content }}
      </span>
      <div class="text-xs text-gray-500 mt-1">
        {{ $message->created_at->format('d/m/Y H:i') }}
      </div>
    </div>
    @endforeach
  </div>
  <form wire:submit.prevent="sendMessage" class="flex mt-4">
    <input type="text" wire:model.defer="newMessage" class="flex-1 rounded-l border-gray-300 focus:ring focus:ring-blue-200" placeholder="Écrire un message...">
    <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r">Envoyer</button>
  </form>
</div>