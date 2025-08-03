<div class="max-w-2xl mx-auto p-6">
  <button onclick="window.history.back()" class="mb-4 flex items-center text-blue-600 hover:text-blue-800 font-semibold">
    <i class="fas fa-arrow-left mr-2"></i>Retour
  </button>
  <h2 class="text-2xl font-bold mb-4">Messages</h2>
  <div class="bg-white rounded shadow p-4 mb-4">
    @if(count($messages) > 0)
    @foreach($messages as $msg)
    <div class="mb-2">
      <span class="font-semibold">{{ $msg->sender->name }}:</span>
      <span>{!! $msg->content !!}</span>
      <span class="text-xs text-gray-400">({{ $msg->created_at->format('d/m/Y H:i') }})</span>
    </div>
    @endforeach
    @else
    <div class="text-center py-8 text-gray-500">
      <i class="fas fa-inbox text-4xl mb-4 text-blue-400"></i>
      <p class="mb-4">Vous n'avez pas de nouveau messages.<br>Pour commencer à en recevoir, réservez un séjour&nbsp;!</p>
      <a href="/" class="inline-block bg-blue-600 text-white font-semibold px-6 py-3 rounded-lg shadow hover:bg-blue-700 transition">RESERVER</a>
    </div>
    @endif
  </div>
</div>