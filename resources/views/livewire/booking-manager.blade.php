<div>
    <div class="container mx-auto py-8">
        <h1 class="block text-3xl font-bold text-gray-800 sm:text-4xl lg:text-6xl lg:leading-tight dark:text-white">Entrez vos dates</h1>

        <form wire:submit.prevent="addBooking" class="mb-4">
            <div class="flex mt-4 flex-col sm:flex-row gap-2 sm:gap-3 items-center bg-white rounded-lg p-2 dark:bg-gray-800">
                <div class="w-full">
                    <p class="py-3 px-4 block w-full border-transparent rounded-lg text-sm 
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent 
                    dark:text-gray-400 dark:focus:ring-gray-600" readonly> {{ $propertyName }} </p>
                </div>

                <div class="w-full">
                    <input type="date" wire:model="checkInDate" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm 
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent 
                    dark:text-gray-400 dark:focus:ring-gray-600" min="{{ now()->format('Y-m-d') }}">
                    @error('checkInDate') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="w-full">
                    <input type="date" wire:model="checkOutDate" wire:change="calculateTotalPrice" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm 
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent 
                    dark:text-gray-400 dark:focus:ring-gray-600" min="{{ $checkInDate }}">
                    @error('checkOutDate') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>
                <button type="submit" wire:submit.prevent="addBooking" id="confirm-booking" class="bg-blue-500 text-white py-2 px-4 rounded">
                    Confirmer
                </button>
            </div>
        </form>
    </div>

    <!-- NavBar -->
    <div class="mt-8">
        <nav class="flex justify-center space-x-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
            <a href="#overview" class="text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Vue d'ensemble</a>
            <a href="#pricing" class="text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Tarifs</a>
            <a href="#amenities" class="text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Équipements</a>
            <a href="#house-rules" class="text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Règles de la maison</a>
            <a href="#info" class="text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">À savoir</a>
            <a href="#reviews" class="text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Avis des clients</a>
        </nav>
    </div>

    <div class="container mx-auto mt-8">
        <!-- Overview section -->
        <div id="overview" class="bg-white shadow-md rounded-lg overflow-hidden w-61 h-90">
            <div class="pl-4 pt-6">
                <h2 class="text-lg text-gray-800">{{ $property->name ?? 'Nom non disponible' }}</h2>
                <p class="text-gray-700">{{ $property->city ?? 'Ville non disponible' }}, {{ $property->district ?? 'Quartier non disponible' }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mt-2 p-4">
                @forelse($property->images as $image)
                <img src="{{ asset('storage/' . $image->image_path) }}" alt="Image de la propriété" class="w-full h-48 object-cover rounded-lg">
                @empty
                <p class="text-gray-500">Aucune image disponible pour cette propriété.</p>
                @endforelse
            </div>
            <div class="p-4">
                <p class="text-gray-500 mt-5">
                    {{ $property->description ?? 'Description non disponible' }}
                </p>
                <p class="text-gray-600 text-right font-bold mt-5">{{ $property->price_per_night ?? 'Prix non disponible' }} € par nuit</p>
                <div class="mt-4">
                    <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}" class="border border-blue-500 bg-white-500 text-blue-500 text-center py-2 px-4 rounded block w-full">Réserver cette résidence</a>
                </div>
            </div>
        </div>

        <!-- Pricing Section -->
        <div id="pricing" class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Tarifs</h2>
            <p class="text-gray-600 dark:text-gray-400">Contenu de la section Tarifs...</p>
        </div>

        <!-- Amenities Section -->
        <div id="amenities" class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Équipements</h2>
            <p class="text-gray-600 dark:text-gray-400">Contenu de la section Équipements...</p>
        </div>

        <!-- House rules section -->
        <div id="house-rules" class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Règles de la maison</h2>
            <p class="text-gray-600 dark:text-gray-400">Contenu de la section Règles de la maison...</p>
        </div>

        <!-- Info section -->
        <div id="info" class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">À savoir</h2>
            <p class="text-gray-600 dark:text-gray-400">Contenu de la section À savoir...</p>
        </div>

        <!-- Reviews section -->
        <div id="reviews" class="mt-8">
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Avis des clients</h2>
            <p class="text-gray-600 dark:text-gray-400">Contenu de la section Avis des clients...</p>
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