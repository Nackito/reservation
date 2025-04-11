<div class="container mx-auto p-4">
    <h2 class="mb-4 text-4xl font-extrabold leading-none tracking-tight 
    text-gray-900 md:text-5xl lg:text-6xl dark:text-white">Mes réservations</h2>

    <!-- Reservations en cours -->


    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @if ($pendingBookings->isEmpty())
        <p class="text-black">Vous n'avez pas de réservation en cours</p>
        @else
        @foreach($pendingBookings as $booking)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4">
                <!-- Affichage de l'image de la propriété -->
                @if($booking->property->images->isNotEmpty())
                <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Image de la propriété" class="w-full h-48 object-cover rounded-lg mb-4">
                @else
                <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="w-full h-48 object-cover rounded-lg mb-4">
                @endif
                <h3 class="text-lg text-gray-800">{{ $booking->property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">Date d'entrée : {{ $booking->start_date }}</p>
                <p class="text-gray-500">Date de sortie : {{ $booking->end_date }}</p>
                <p class="text-gray-600">Prix total : {{ $booking->total_price }} €</p>
                <p class="text-gray-400">Soumit le : {{ $booking->created_at }}</p>
                <div class="mt-4 flex justify-between">
                    @if(\Carbon\Carbon::now()->gte(\Carbon\Carbon::parse($booking->start_date)))
                    <button wire:click="openReviewModal({{ $booking->id }})" class="bg-blue-500 text-white py-1 px-2 rounded">Laissez un avis</button>
                    @else
                    <button wire:click="deleteBooking({{ $booking->id }})" class="bg-red-500 text-white py-1 px-2 rounded">Annuler</button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
        @endif
    </div>

    <!-- Boutons pour changer d'onglet -->
    <div class="pt-4">
        <button wire:click="setActiveTab('pending')" class="py-2.5 px-5 me-2 mb-2 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg 
        border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 
        focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 
        dark:text-gray-400 dark:border-gray-600 dark:hover:text-white 
        dark:hover:bg-gray-700 {{ $activeTab === 'pending' ? 'bg-blue-500 text-white' : '' }}">En attente</button>

        <button wire:click="setActiveTab('past')" class="py-2.5 px-5 me-2 mb-2 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg 
        border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 
        focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 
        dark:text-gray-400 dark:border-gray-600 dark:hover:text-white 
        dark:hover:bg-gray-700 {{ $activeTab === 'past' ? 'bg-blue-500 text-white' : '' }}">Passées</button>

        <button wire:click="setActiveTab('canceled')" class="py-2.5 px-5 me-2 mb-2 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg 
        border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 
        focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 
        dark:text-gray-400 dark:border-gray-600 dark:hover:text-white 
        dark:hover:bg-gray-700 {{ $activeTab === 'canceled' ? 'bg-blue-500 text-white' : '' }}">Annulés</button>
    </div>

    <!-- Affichage des réservations -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @if ($activeTab === 'pending')
        @if ($pendingBookings->isEmpty())
        <p class="text-black">Vous n'avez pas de réservation en attente</p>
        @else
        @foreach($pendingBookings as $booking)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4">
                <!-- Affichage de l'image de la propriété -->
                @if($booking->property->images->isNotEmpty())
                <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Image de la propriété" class="w-full h-48 object-cover rounded-lg mb-4">
                @else
                <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="w-full h-48 object-cover rounded-lg mb-4">
                @endif
                <h3 class="text-lg text-gray-800">{{ $booking->property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">Date d'entrée : {{ $booking->start_date }}</p>
                <p class="text-gray-500">Date de sortie : {{ $booking->end_date }}</p>
                <p class="text-gray-600">Prix total : {{ $booking->total_price }} €</p>
            </div>
        </div>
        @endforeach
        @endif
        @elseif ($activeTab === 'past')
        @if ($pastBookings->isEmpty())
        <p class="text-black">Vous n'avez pas de réservation passée</p>
        @else
        @foreach($pastBookings as $booking)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4">
                <!-- Affichage de l'image de la propriété -->
                @if($booking->property->images->isNotEmpty())
                <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Image de la propriété" class="w-full h-48 object-cover rounded-lg mb-4">
                @else
                <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="w-full h-48 object-cover rounded-lg mb-4">
                @endif
                <h3 class="text-lg text-gray-800">{{ $booking->property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">Date d'entrée : {{ $booking->start_date }}</p>
                <p class="text-gray-500">Date de sortie : {{ $booking->end_date }}</p>
                <p class="text-gray-600">Prix total : {{ $booking->total_price }} €</p>
                <div class="mt-4 flex justify-between">
                    @if(\Carbon\Carbon::now()->gte(\Carbon\Carbon::parse($booking->start_date)))
                    <button wire:click="openReviewModal({{ $booking->id }})" class="bg-blue-500 text-white py-1 px-2 rounded">Laissez un avis</button>
                    @else
                    <button wire:click="deleteBooking({{ $booking->id }})" class="bg-red-500 text-white py-1 px-2 rounded">Annuler</button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
        @endif
        @elseif ($activeTab === 'canceled')
        @if ($canceledBookings->isEmpty())
        <p class="text-black">Vous n'avez pas de réservation annulée</p>
        @else
        @foreach($canceledBookings as $booking)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4">
                <!-- Affichage de l'image de la propriété -->
                @if($booking->property->images->isNotEmpty())
                <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Image de la propriété" class="w-full h-48 object-cover rounded-lg mb-4">
                @else
                <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="w-full h-48 object-cover rounded-lg mb-4">
                @endif
                <h3 class="text-lg text-gray-800">{{ $booking->property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">Date d'entrée : {{ $booking->start_date }}</p>
                <p class="text-gray-500">Date de sortie : {{ $booking->end_date }}</p>
                <p class="text-gray-600">Prix total : {{ $booking->total_price }} €</p>
            </div>
        </div>
        @endforeach
        @endif
        @endif
    </div>


    <!-- Modale pour laisser un avis -->
    @if($showReviewModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-1/2 p-6">
            <h2 class="text-2xl font-bold mb-4">Laisser un avis</h2>
            <form wire:submit.prevent="submitReview">
                <textarea wire:model="review" class="w-full p-2 border rounded-lg mb-4" placeholder="Écrivez votre avis ici..."></textarea>
                @error('review') <span class="text-red-500">{{ $message }}</span> @enderror

                <div class="flex items-center space-x-1 mb-4">
                    @for($i = 1; $i <= 5; $i++)
                        <label class="cursor-pointer">
                        <!-- Bouton radio caché -->
                        <input type="radio" wire:model="rating" value="{{ $i }}" class="hidden" />
                        <!-- Icône SVG pour l'étoile -->
                        <svg wire:click="$set('rating', {{ $i }})" class="w-6 h-6 {{ $rating >= $i ? 'text-yellow-500' : 'text-gray-400' }} hover:text-yellow-500 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" />
                        </svg>
                        </label>
                        @endfor
                </div>
                @error('rating') <span class="text-red-500">{{ $message }}</span> @enderror

                <div class="flex justify-end space-x-2">
                    <button type="button" wire:click="closeReviewModal" class="bg-gray-500 text-white py-2 px-4 rounded">Annuler</button>
                    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">Envoyer</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>