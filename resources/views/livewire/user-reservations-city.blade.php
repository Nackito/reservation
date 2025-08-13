@include('components.header')
@livewire('partials.navbar')
<div class="container mx-auto p-4">
  <div class="mt-6 mb-4">
    <a href="{{ route('user-reservations') }}" class="text-blue-500 hover:underline">&larr; Retour à mes réservations</a>
  </div>
  <h2 class="mb-4 text-3xl font-extrabold leading-none tracking-tight text-gray-900 dark:text-white">
    Détail des réservations pour {{ $city }}
  </h2>
  @if($residences->isEmpty())
  <p class="text-gray-700">Aucune résidence réservée dans cette ville.</p>
  @else
  <div class="space-y-4">
    @foreach($residences as $booking)
    <div class="flex items-center justify-between bg-white rounded-lg shadow p-4">
      <div>
        <div class="font-bold text-lg">{{ $booking->property->name }}</div>
        <div class="text-gray-600 text-sm">{{ $booking->start_date }} - {{ $booking->end_date }}</div>
        <div class="text-gray-500 text-xs">Prix payé : {{ $booking->total_price }} €</div>
      </div>
      <div>
        <form action="{{ route('user-reservations.review', $booking->id) }}" method="GET">
          <button type="submit" class="bg-blue-500 text-white px-3 py-1 rounded text-xs">Laisser un avis</button>
        </form>
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>
@livewire('partials.footer')