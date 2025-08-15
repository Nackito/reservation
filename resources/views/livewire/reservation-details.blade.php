<div class="container mx-auto p-4">
    <h2 class="text-4xl font-bold mb-4">Détails des réservations</h2>

    @foreach($reservations as $reservation)
    <div class="row flex flex-col bg-white shadow-md rounded-lg overflow-hidden mb-4">
        <div class="p-4 flex flex-col md:flex-row">
            <!-- Affichage de l'image de la propriété -->
            @if($reservation->property->images->isNotEmpty())
            <img src="{{ asset('storage/' . $reservation->property->images->first()->image_path) }}" alt="Image de la propriété" class="object-cover w-full rounded-t-lg h-96 md:h-auto md:w-48 md:rounded-none md:rounded-s-lg">
            @else
            <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="object-cover w-full rounded-t-lg h-96 md:h-auto md:w-48 md:rounded-none md:rounded-s-lg">
            @endif
            <div class="flex flex-col justify-between flex-grow p-4">
                <div>
                    <h3 class="text-lg text-gray-800">{{ $reservation->property->name ?? 'Nom non disponible' }}</h3>
                    <p class="text-gray-500">{{ $reservation->start_date }} - {{ $reservation->end_date }}</p>
                    <p class="text-gray-600">{{ $reservation->total_price }} FrCFA</p>
                    <p class="text-gray-400">Soumis le : {{ $reservation->created_at }}</p>
                </div>
                <div class="flex justify-end mt-4">
                    @if($this->hasReview($reservation->property->id))
                    <button wire:click="openEditReviewModal({{ $reservation->property->id }})" class="bg-yellow-500 text-white py-1 px-2 rounded">Modifier l'avis</button>
                    @else
                    <button wire:click="openReviewModal({{ $reservation->id }})" class="bg-blue-500 text-white py-1 px-2 rounded">Laissez un avis</button>
                    @endif

                </div>
            </div>
        </div>
    </div>
    @endforeach


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