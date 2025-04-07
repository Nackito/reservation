<div>
    <div class="container mx-auto py-8">
        <h1 class="block text-3xl font-bold text-gray-800 sm:text-4xl lg:text-6xl lg:leading-tight dark:text-white">Entrez vos dates</h1>

        <form wire:submit.prevent="addBooking" class="mb-4">
            <div class="mb-4">
                <label for="checkInDate" class="block text-gray-700">Date d'entrée</label>
                <input type="date" wire:model="checkInDate" class="border p-2 rounded w-full">
                @error('checkInDate') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="mb-4">
                <label for="checkOutDate" class="block text-gray-700">Date de sortie</label>
                <input type="date" wire:model="checkOutDate" wire:change="calculateTotalPrice" class="border p-2 rounded w-full">
                @error('checkOutDate') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <button type="submit" wire:submit.prevent="addBooking" id="confirm-booking" class="bg-blue-500 text-white py-2 px-4 rounded">
                Confirmer la réservation
            </button>
        </form>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden w-61 h-90">
        @if($property->firstImage())
        <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}" alt="{{ $property->name }}" class="w-full h-48 object-cover">
        @else
        <img src="{{ asset('images/default-image.jpg') }}" alt="Image par défaut" class="w-full h-48 object-cover">
        @endif
        <div class="p-4">
            <h3 class="text-lg text-gray-800">{{ $property->name ?? 'Nom non disponible' }}</h3>
            <p class="text-gray-700">{{ $property->city ?? 'Ville non disponible' }}, {{ $property->district ?? 'Quartier non disponible' }}</p>
            <p class="text-gray-500 mt-5">
                {{ Str::words($property->description ?? 'Description non disponible', 20, '...') }}
            </p>
            <p class="text-gray-600 text-right font-bold mt-5">{{ $property->price_per_night ?? 'Prix non disponible' }} € par nuit</p>
            <div class="mt-4">
                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}" class="border border-blue-500 bg-white-500 text-blue-500 text-center py-2 px-4 rounded block w-full">Réserver cette résidence</a>
            </div>
        </div>
    </div>

    <!-- Preline Modal -->
    <div id="confirmationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="relative w-full max-w-lg p-4 mx-auto bg-white rounded-lg shadow-lg">
                <div class="flex justify-between items-center pb-3">
                    <h3 class="text-lg font-semibold">Confirmation de réservation</h3>
                    <button class="text-gray-400 hover:text-gray-600" onclick="closeModal()">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mt-2">
                    <p class="text-gray-700 dark:text-white">Vous allez payer <span id="totalPrice"></span> € pour cette réservation. Voulez-vous confirmer ?</p>
                </div>
                <div class="flex justify-end pt-4">
                    <button class="bg-gray-500 text-white px-4 py-2 rounded mr-2" onclick="closeModal()">Annuler</button>
                    <button class="bg-blue-500 text-white px-4 py-2 rounded" onclick="confirmBooking()">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function closeModal() {
            document.getElementById('confirmationModal').classList.add('hidden');
        }

        function confirmBooking() {
            document.getElementById('confirm-booking').click();
            closeModal();
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.addEventListener('show-confirmation', event => {
                document.getElementById('totalPrice').textContent = event.detail.totalPrice;
                document.getElementById('confirmationModal').classList.remove('hidden');
            });
        });
    </script>
</div>