<div class="container mx-auto p-4">
    <h2 class="mb-4 text-4xl font-extrabold leading-none tracking-tight 
    text-gray-900 md:text-5xl lg:text-6xl dark:text-black">Mes réservations</h2>

    <!-- Reservations en cours -->


    <div class="container mx-auto p-4">
        @if ($pendingBookings->isEmpty())
        <div class="row flex items-center">
            <div class="w-1/3">
                <img src="{{ asset('images/photo5.jpg') }}" alt="Image par défaut" class="rounded-full w-96 h-96">
            </div>
            <div class="w-2/3 p-4">
                <p class="text-4xl font-bold text-gray-900 dark:text-white mb-4">Vous preferez avec ou sans Jaccuzy?</p>
                <p class="text-black text-2xl">Lorsque vous aurez effectué une réservation, elle apparaîtra ici.</p>
            </div>
        </div>
        @else

        @foreach($pendingBookings as $booking)
        <div class="flex bg-white rounded-lg overflow-hidden mb-2 max-w-sm transition-shadow duration-200 hover:shadow-md">
            <div class="flex-shrink-0 w-24 h-24">
                @if($booking->property->images->isNotEmpty())
                <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Image de la propriété" class="object-cover w-full h-full rounded-lg">
                @else
                <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="object-cover w-full h-full rounded-lg">
                @endif
            </div>
            <div class="flex flex-col justify-between p-3 flex-1">
                <h5 class="text-base font-bold">{{ $booking->property->name ?? 'Nom non disponible' }}</h5>
                <p class="text-gray-700 text-sm">{{ $booking->start_date }} - {{ $booking->end_date }}</p>
                <p class="text-gray-500 text-xs">{{ $booking->total_price }} €</p>
                <p class="text-gray-400 text-xs">Soumis le : {{ $booking->created_at }}</p>
                <div class="mt-2 flex justify-between">
                    @if(\Carbon\Carbon::now()->gte(\Carbon\Carbon::parse($booking->start_date)))
                    <button wire:click="openReviewModal({{ $booking->id }})" class="bg-blue-500 text-white py-1 px-2 rounded text-xs">Laissez un avis</button>
                    @else
                    <button wire:click="deleteBooking({{ $booking->id }})" class="bg-red-500 text-white py-1 px-2 rounded text-xs">Annuler</button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
        @endif

        <!-- Boutons pour changer d'onglet -->
        <div class="pt-4">

            <button wire:click="setActiveTab('past')" class="py-2.5 px-5 me-2 mb-2 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg 
        border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 
        focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 
        dark:text-gray-400 dark:border-gray-600 dark:hover:text-white 
        dark:hover:bg-gray-700 {{ $activeTab === 'past' ? 'bg-blue-500 text-white' : '' }}">Passés</button>

            <button wire:click="setActiveTab('canceled')" class="py-2.5 px-5 me-2 mb-2 text-sm font-medium text-gray-900 focus:outline-none bg-white rounded-lg 
        border border-gray-200 hover:bg-gray-100 hover:text-blue-700 focus:z-10 
        focus:ring-4 focus:ring-gray-100 dark:focus:ring-gray-700 dark:bg-gray-800 
        dark:text-gray-400 dark:border-gray-600 dark:hover:text-white 
        dark:hover:bg-gray-700 {{ $activeTab === 'canceled' ? 'bg-blue-500 text-white' : '' }}">Annulés</button>
        </div>

        <!-- Affichage des réservations -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @if ($activeTab === 'past')
            @if ($groupedPastBookings->isEmpty())
            <p class="text-black">Vous n'avez pas de réservation passée</p>
            @else
            @foreach($groupedPastBookings as $group)
            <div>
                <a href="{{ route('user-reservations.city', ['city' => urlencode($group['city'])]) }}" class="block">
                    <div class="flex bg-white rounded-lg overflow-hidden mb-2 max-w-sm transition-shadow duration-200 hover:shadow-md border border-gray-200 cursor-pointer">
                        <div class="flex-shrink-0 w-24 h-24">
                            @if($group['image'])
                            <img src="{{ asset('storage/' . $group['image']->image_path) }}" alt="Image de la ville" class="object-cover w-full h-full rounded-lg">
                            @else
                            <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="object-cover w-full h-full rounded-lg">
                            @endif
                        </div>
                        <div class="flex flex-col justify-between p-3 flex-1">
                            <h3 class="text-base font-bold">{{ $group['city'] }}</h3>
                            <p class="text-gray-700 text-sm">{{ $group['count'] }} résidence(s) réservée(s)</p>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
            @endif
            @elseif ($activeTab === 'canceled')
            @if ($canceledBookings->isEmpty())
            <p class="text-black">Vous n'avez pas de réservation annulée</p>
            @else
            @foreach($canceledBookings as $booking)
            <a href="#" class="flex flex-col items-center bg-white border border-gray-200 rounded-lg shadow-sm md:flex-row md:max-w-xl 
            hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 
            dark:hover:bg-gray-700">
                <!-- Affichage de l'image de la propriété -->
                @if($booking->property->images->isNotEmpty())
                <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Image de la propriété" class="object-cover w-full rounded-t-lg h-96 md:h-auto md:w-48 md:rounded-none md:rounded-s-lg">
                @else
                <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="object-cover w-full rounded-t-lg h-96 md:h-auto md:w-48 md:rounded-none md:rounded-s-lg">
                @endif
                <div class="flex flex-col justify-between p-4 leading-normal">
                    <h3 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $booking->property->name ?? 'Nom non disponible' }}</h3>
                    <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">{{ $booking->start_date }} - {{ $booking->end_date }}</p>
                    <p class="mb-3 font-normal text-gray-700 dark:text-gray-400">Prix total : {{ $booking->total_price }} €</p>
                    <p class="text-red-500">Annulé</p>
                </div>
            </a>
            @endforeach
            @endif
            @endif
        </div>


        <!-- Modale pour laisser un avis -->
        @if($showReviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-1/2 p-6">
                <h2 class="text-2xl font-bold mb-4">
                    {{ $editReviewId ? 'Modifier votre avis' : 'Laisser un avis' }}
                </h2>
                <form wire:submit.prevent="{{ $editReviewId ? 'updateReview' : 'submitReview' }}">
                    <textarea wire:model="{{ $editReviewId ? 'editReviewContent' : 'review' }}" class="w-full p-2 border rounded-lg mb-4" placeholder="Écrivez votre avis ici..."></textarea>
                    @error($editReviewId ? 'editReviewContent' : 'review') <span class="text-red-500">{{ $message }}</span> @enderror

                    <div class="flex items-center space-x-1 mb-4">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer">
                            <input type="radio" wire:model="{{ $editReviewId ? 'editReviewRating' : 'rating' }}" value="{{ $i }}" class="hidden" />
                            <svg class="w-6 h-6 {{ ($editReviewId ? $editReviewRating : $rating) >= $i ? 'text-yellow-500' : 'text-gray-400' }} hover:text-yellow-500 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" />
                            </svg>
                            </label>
                            @endfor
                    </div>
                    @error($editReviewId ? 'editReviewRating' : 'rating') <span class="text-red-500">{{ $message }}</span> @enderror

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeReviewModal" class="bg-gray-500 text-white py-2 px-4 rounded">Annuler</button>
                        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">
                            {{ $editReviewId ? 'Mettre à jour' : 'Envoyer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>