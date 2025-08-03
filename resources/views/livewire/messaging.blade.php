<div class="max-w-2xl mx-auto p-6">
  <h2 class="text-2xl font-bold mb-4">Messagerie interne</h2>
  <div class="bg-white rounded shadow p-4 mb-4">
    @foreach($messages as $msg)
    <div class="mb-2">
      <span class="font-semibold">{{ $msg->sender->name }}:</span>
      <span>{{ $msg->content }}</span>
      <span class="text-xs text-gray-400">({{ $msg->created_at->format('d/m/Y H:i') }})</span>
    </div>
    @endforeach
  </div>
  <form wire:submit.prevent="sendMessage({{ Auth::id() }})" class="flex gap-2">
    <input type="text" wire:model="newMessage" class="flex-1 border rounded px-2 py-1" placeholder="Votre message...">
    <button type="submit" class="bg-blue-500 text-white px-4 py-1 rounded">Envoyer</button>
  </form>
</div>