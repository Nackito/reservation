<div class="container mx-auto p-4">
  <div class="mt-6 mb-4">
    <a href="{{ route('user-reservations') }}" class="text-blue-500 dark:text-blue-400 hover:underline">&larr; Retour à mes réservations</a>
  </div>
  <h2 class="mb-4 text-3xl font-extrabold leading-none tracking-tight text-gray-900 dark:text-gray-100">
    Détail des réservations pour {{ $city }}
  </h2>
  @if($residences->isEmpty())
  <p class="text-gray-700 dark:text-gray-300">Aucune résidence réservée dans cette ville.</p>
  @else
  <div class="space-y-4">
    @foreach($residences as $booking)
    <div class="flex items-center justify-between bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <div class="flex items-center">
        @if($booking->property->images && $booking->property->images->count())
        <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Photo de la propriété" class="w-20 h-20 object-cover rounded mr-4 bg-gray-100 dark:bg-gray-700">
        @else
        <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="w-20 h-20 object-cover rounded mr-4 bg-gray-100 dark:bg-gray-700">
        @endif
        <div>
          <div class="font-bold text-lg text-gray-900 dark:text-gray-100">{{ $booking->property->name }}</div>
          <div class="text-gray-600 dark:text-gray-300 text-sm">{{ $booking->start_date }} - {{ $booking->end_date }}</div>
          @php
          $user = auth()->user();
          $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
          $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
          $converted = $rate ? round($booking->total_price * $rate, 2) : $booking->total_price;
          @endphp
          <div class="text-gray-800 dark:text-gray-200 text-lg">{{ number_format($converted, 2) }} {{ $userCurrency }}</div>
        </div>
      </div>
      <div class="relative flex flex-col items-end">
        @php $userReview = $booking->property->reviews->first(); @endphp
        <div x-data="{ open: false }" class="relative">
          <button @click="open = !open" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-600 dark:text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <circle cx="12" cy="5" r="1.5" />
              <circle cx="12" cy="12" r="1.5" />
              <circle cx="12" cy="19" r="1.5" />
            </svg>
          </button>
          <div x-show="open" @click.away="open = false" class="absolute right-0 mt-2 w-40 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded shadow-lg z-10">
            @if($userReview)
            <form action="{{ route('user-reservations.review', $booking->id) }}" method="GET">
              <input type="hidden" name="edit" value="1">
              <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-yellow-700 dark:text-yellow-100 hover:bg-yellow-50 dark:hover:bg-yellow-600">Modifier mon avis</button>
            </form>
            @else
            <form action="{{ route('user-reservations.review', $booking->id) }}" method="GET">
              <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-blue-700 dark:text-blue-100 hover:bg-blue-50 dark:hover:bg-blue-600">Laisser un avis</button>
            </form>
            @endif
            <button wire:click="deleteBooking({{ $booking->id }})" onclick="return confirm('Voulez-vous vraiment supprimer cette réservation ?')" class="block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-100 hover:bg-red-50 dark:hover:bg-red-600">Supprimer</button>
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>