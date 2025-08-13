<div class="container mx-auto p-4">
  <div class="mt-6 mb-4">
    <a href="{{ route('user-reservations') }}" class="text-blue-500 hover:underline">&larr; Retour à mes réservations</a>
  </div>
  <h2 class="mb-4 text-3xl font-extrabold leading-none tracking-tight text-gray-900 dark:text-gray-800">
    Réservations annulées pour {{ $city }}
  </h2>
  @if($canceled->isEmpty())
  <p class="text-gray-700">Aucune réservation annulée dans cette ville.</p>
  @else
  <div class="space-y-4">
    @foreach($canceled as $booking)
    <div class="flex items-center justify-between bg-white rounded-lg shadow p-4">
      <div class="flex items-center">
        @if($booking->property->images && $booking->property->images->count())
        <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Propriété" class="w-20 h-20 object-cover rounded mr-4">
        @else
        <img src="{{ asset('images/default-property.jpg') }}" alt="Défaut" class="w-20 h-20 object-cover rounded mr-4">
        @endif
        <div>
          <div class="font-bold text-lg">{{ $booking->property->name }}</div>
          <div class="text-gray-600 text-sm">{{ $booking->start_date }} - {{ $booking->end_date }}</div>
          <div class="text-gray-800 text-lg">{{ $booking->total_price }} FrCFA</div>
        </div>
      </div>
      <span class="inline-block bg-red-100 text-red-700 text-xs px-2 py-1 rounded">Annulé</span>
    </div>
    @endforeach
  </div>
  @endif
</div>