<div>
    <h1 class="text-2xl font-bold mb-4">Gestion des Réservations</h1>

    <form wire:submit.prevent="addBooking" class="mb-4">
        <div class="mb-4">
            <label for="checkInDate" class="block text-gray-700">Date d'entrée</label>
            <input type="date" wire:model="checkInDate" class="border p-2 rounded w-full">
            @error('checkInDate') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label for="checkOutDate" class="block text-gray-700">Date de sortie</label>
            <input type="date" wire:model="checkOutDate" class="border p-2 rounded w-full">
            @error('checkOutDate') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <button type="button" wire:click="calculateTotalPrice" class="bg-blue-500 text-white py-2 px-4 rounded">Valider</button>
        </div>
        @if($totalPrice)
        <div class="mb-4">
            <p class="text-gray-700">Vous allez payer {{ $totalPrice }} € pour cette réservation</p>
        </div>
        @endif
        <button type="submit" wire:submit.prevent="addBooking" wire:confirm="Êtes-vous sûr de vouloir confirmer cette réservation ?">
            Confirmer la réservation
        </button>
    </form>
</div>