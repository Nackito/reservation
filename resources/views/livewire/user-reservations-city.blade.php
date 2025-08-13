<div class="container mx-auto p-4">
  <div class="mt-6 mb-4">
    <a href="{{ route('user-reservations') }}" class="text-blue-500 hover:underline">&larr; Retour à mes réservations</a>
  </div>
  <h2 class="mb-4 text-3xl font-extrabold leading-none tracking-tight text-gray-900 dark:text-black">
    Détail des réservations pour {{ $city }}
  </h2>
  @if($residences->isEmpty())
  <p class="text-gray-700">Aucune résidence réservée dans cette ville.</p>
  @else
  <div class="space-y-4">
    @foreach($residences as $booking)
    <div class="flex items-center justify-between bg-white rounded-lg shadow p-4">
      <div class="flex items-center">
        @if($booking->property->images && $booking->property->images->count())
        <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Photo de la propriété" class="w-20 h-20 object-cover rounded mr-4">
        @else
        <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="w-20 h-20 object-cover rounded mr-4">
        @endif
        <div>
          <div class="font-bold text-lg">{{ $booking->property->name }}</div>
          <div class="text-gray-600 text-sm">{{ $booking->start_date }} - {{ $booking->end_date }}</div>
          <div class="text-gray-800 text-lg">{{ $booking->total_price }} FrCFA</div>
        </div>
      </div>
      <div class="flex flex-col gap-2 items-end">
        @php
        $userReview = $booking->property->reviews->first();
        @endphp
        <div class="flex gap-2">
          @if($userReview)
          <form action="{{ route('user-reservations.review', $booking->id) }}" method="GET">
            <input type="hidden" name="edit" value="1">
            <button type="submit" class="bg-yellow-500 text-white px-3 py-1 rounded text-xs">Modifier mon avis</button>
          </form>
          @else
          <form action="{{ route('user-reservations.review', $booking->id) }}" method="GET">
            <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded text-xs">Laisser un avis</button>
          </form>
          @endif
          <button wire:click="deleteBooking({{ $booking->id }})" onclick="return confirm('Voulez-vous vraiment supprimer cette réservation ?')" class="bg-red-500 text-white px-3 py-1 rounded text-xs">Supprimer</button>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>