<div class="container mx-auto p-4">
    <h2 class="text-xl font-bold mb-4">Mes réservations</h2>
    <h3 class="text-lg font-bold mb-4">En attente</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @if ($pendingBookings->isEmpty())
        <p class="text-black">Vous n'avez pas de réservation en attente</p>
        @else
        @foreach($pendingBookings as $booking)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4">
                <h3 class="text-lg text-gray-800">{{ $booking->property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">Date d'entrée : {{ $booking->start_date }}</p>
                <p class="text-gray-500">Date de sortie : {{ $booking->end_date }}</p>
                <p class="text-gray-600">Prix total : {{ $booking->total_price }} €</p>
                <p class="text-gray-400">Soumit le : {{ $booking->created_at }}</p>
                <div class="mt-4 flex justify-between">
                    <button wire:click="deleteBooking({{ $booking->id }})" class="bg-red-500 text-white py-1 px-2 rounded">Annuler</button>
                </div>
            </div>
        </div>
        @endforeach
        @endif
    </div>

    <h3 class="text-lg font-bold mb-4">Accepté</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($acceptedBookings as $booking)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4">
                <h3 class="text-lg text-gray-800">{{ $booking->property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">Date d'entrée : {{ $booking->start_date }}</p>
                <p class="text-gray-500">Date de sortie : {{ $booking->end_date }}</p>
                <p class="text-gray-600">Prix total : {{ $booking->total_price }} €</p>
                <p class="text-gray-400">Votre reservation a été accepté</p>
                <div class="mt-4 flex justify-between">
                    <button wire:click="deleteBooking({{ $booking->id }})" class="bg-red-500 text-white py-1 px-2 rounded">Annuler</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>