<div>
    <h1 class="text-2xl font-bold mb-4">Gestion des Réservations</h1>

    <form wire:submit.prevent="addBooking" class="mb-4">
        <input type="text" wire:model="newBooking" class="border p-2 rounded w-full" placeholder="Nouvelle réservation">
        @error('newBooking') <span class="text-red-500">{{ $message }}</span> @enderror
        <button type="submit" class="bg-primary text-white py-2 px-4 rounded mt-2">Ajouter</button>
    </form>

    <ul>
        @foreach($this->bookings as $booking)
        <li class="border-b py-2">{{ $booking->name }}</li>
        @endforeach
    </ul>

    <script>
        window.addEventListener('booking-added', event => {
            alert(event.detail.message);
        });
    </script>
</div>