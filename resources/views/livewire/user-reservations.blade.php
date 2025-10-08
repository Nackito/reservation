<div class="container mx-auto p-4 bg-white dark:bg-gray-900 min-h-screen">
    <h2 class="mb-4 text-4xl font-extrabold leading-none tracking-tight text-gray-900 dark:text-gray-100 md:text-5xl lg:text-6xl">Mes réservations</h2>

    @if (session('status'))
    <div class="mb-4 p-3 rounded-md border text-sm
            bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800">
        {{ session('status') }}
    </div>
    @endif

    <!-- Reservations en cours -->


    <div class="container mx-auto p-4">
        @if ($pendingBookings->isEmpty())
        <div class="flex flex-col md:flex-row items-center">
            <div class="w-full md:w-1/3 flex justify-center mb-6 md:mb-0">
                <img src="{{ asset('images/photo5.jpg') }}" alt="Image par défaut" class="rounded-full w-60 h-60 md:w-96 md:h-96 object-cover bg-gray-100 dark:bg-gray-700">
            </div>
            <div class="w-full md:w-2/3 p-4 text-center md:text-left">
                <p class="text-2xl md:text-4xl font-bold text-gray-900 dark:text-gray-100 mb-4">Vous préférez avec ou sans Jaccuzy&nbsp;?</p>
                <p class="text-black dark:text-gray-200 text-lg md:text-2xl">Lorsque vous aurez effectué une réservation, elle apparaîtra ici.</p>
            </div>
        </div>
        @else

        @foreach($pendingBookings as $booking)
        <div class="flex bg-white dark:bg-gray-800 rounded-lg overflow-hidden mb-2 max-w-sm transition-shadow duration-200 hover:shadow-md">
            <div class="flex-shrink-0 w-24 h-24">
                @if($booking->property->images->isNotEmpty())
                <img src="{{ asset('storage/' . $booking->property->images->first()->image_path) }}" alt="Image de la propriété" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
                @else
                <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
                @endif
            </div>
            <div class="flex flex-col justify-between p-3 flex-1">
                <h5 class="text-base font-bold text-gray-800 dark:text-gray-100">{{ $booking->property->name ?? 'Nom non disponible' }}</h5>
                <p class="text-gray-700 dark:text-gray-300 text-sm">{{ $booking->start_date }} - {{ $booking->end_date }}</p>
                @if (!empty($booking->payment_status))
                @php
                $paid = $booking->payment_status === 'paid';
                $badgeClasses = $paid
                ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-200 dark:border-green-800'
                : 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-200 dark:border-yellow-800';
                @endphp
                <span class="inline-block w-fit text-[11px] px-2 py-0.5 rounded border {{ $badgeClasses }}">
                    {{ $paid ? 'Payée' : 'En attente de paiement' }}
                    @if($paid && $booking->paid_at)
                    • {{ $booking->paid_at->format('d/m/Y H:i') }}
                    @endif
                </span>
                @endif
                @php
                $user = auth()->user();
                $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
                $displayCurrency = ($rate && $rate > 0) ? $userCurrency : 'XOF';
                $converted = ($rate && $rate > 0 && $booking->total_price !== null) ? round($booking->total_price * $rate, 2) : $booking->total_price;
                @endphp
                <p class="text-gray-500 dark:text-gray-200 text-xs">
                    {{ number_format($converted, 2) }} {{ $displayCurrency }}
                </p>
                <p class="text-gray-400 dark:text-gray-400 text-xs">Soumis le : {{ $booking->created_at }}</p>
                <div class="mt-2 flex justify-between">
                    @php $userReview = $booking->property->reviews->first(); @endphp
                    @if(\Carbon\Carbon::now()->gte(\Carbon\Carbon::parse($booking->end_date)))
                    @if($userReview)
                    <form action="{{ route('user-reservations.review', $booking->id) }}" method="GET">
                        <input type="hidden" name="edit" value="1">
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-yellow-700 dark:text-yellow-100 bg-yellow-500 rounded hover:bg-yellow-50 dark:hover:bg-yellow-600">Modifier mon avis</button>
                    </form>
                    @else
                    <form action="{{ route('user-reservations.review', $booking->id) }}" method="GET">
                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-blue-700 dark:text-blue-100 bg-blue-500 rounded hover:bg-blue-50 dark:hover:bg-blue-600">Laisser un avis</button>
                    </form>
                    @endif
                    @else
                    <button wire:click="deleteBooking({{ $booking->id }})" class="bg-red-500 hover:bg-red-600 text-white py-1 px-2 rounded text-xs transition">Annuler</button>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
        @endif

        <!-- Boutons pour changer d'onglet -->
        <div class="pt-4">
            <button
                wire:click="setActiveTab('past')"
                class="py-2.5 px-5 me-2 mb-2 text-sm font-medium focus:outline-none rounded-lg border focus:z-10 focus:ring-4
        {{ $activeTab === 'past'
            ? 'bg-blue-500 text-white border-blue-500 focus:ring-blue-200'
            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-blue-700 dark:hover:text-white focus:ring-gray-100 dark:focus:ring-gray-700' }}">
                Passés
            </button>

            <button
                wire:click="setActiveTab('canceled')"
                class="py-2.5 px-5 me-2 mb-2 text-sm font-medium focus:outline-none rounded-lg border focus:z-10 focus:ring-4
        {{ $activeTab === 'canceled'
            ? 'bg-blue-500 text-white border-blue-500 focus:ring-blue-200'
            : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-600 text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-blue-700 dark:hover:text-white focus:ring-gray-100 dark:focus:ring-gray-700' }}">
                Annulés
            </button>
        </div>

        <!-- Affichage des réservations -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @if ($activeTab === 'past')
            @if ($groupedPastBookings->isEmpty())
            <p class="text-black dark:text-gray-200">Vous n'avez pas de réservation passée</p>
            @else
            @foreach($groupedPastBookings as $group)
            <div>
                <a href="{{ route('user-reservations.city', ['city' => urlencode($group['city'])]) }}" class="block">
                    <div class="flex bg-white dark:bg-gray-800 rounded-lg overflow-hidden mb-2 max-w-sm transition-shadow duration-200 hover:shadow-md border border-gray-200 dark:border-gray-700 cursor-pointer">
                        <div class="flex-shrink-0 w-24 h-24">
                            @if($group['image'])
                            <img src="{{ asset('storage/' . $group['image']->image_path) }}" alt="Image de la ville" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
                            @else
                            <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
                            @endif
                        </div>
                        <div class="flex flex-col justify-between p-3 flex-1">
                            <h3 class="text-base font-bold text-gray-800 dark:text-gray-100">{{ $group['city'] }}</h3>
                            <p class="text-gray-700 dark:text-gray-300 text-sm">{{ $group['count'] }} résidence(s) réservée(s)</p>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
            @endif
            @elseif ($activeTab === 'canceled')
            @if ($canceledBookings->isEmpty())
            <p class="text-black dark:text-gray-200">Vous n'avez pas de réservation annulée</p>
            @else
            @php
            $groupedCanceledBookings = $canceledBookings->groupBy(function($booking) {
            return $booking->property->city ?? 'Ville inconnue';
            });
            @endphp
            @foreach($groupedCanceledBookings as $city => $bookings)
            <div>
                <a href="{{ route('user-canceled-reservations.city', ['city' => urlencode($city)]) }}" class="block">
                    <div class="flex bg-white dark:bg-gray-800 rounded-lg overflow-hidden mb-2 max-w-sm transition-shadow duration-200 hover:shadow-md border border-gray-200 dark:border-gray-700 cursor-pointer">
                        <div class="flex-shrink-0 w-24 h-24">
                            @if($bookings->first()->property->images->isNotEmpty())
                            <img src="{{ asset('storage/' . $bookings->first()->property->images->first()->image_path) }}" alt="Image de la ville" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
                            @else
                            <img src="{{ asset('images/default-property.jpg') }}" alt="Image par défaut" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
                            @endif
                        </div>
                        <div class="flex flex-col justify-between p-3 flex-1">
                            <h3 class="text-base font-bold text-gray-800 dark:text-gray-100">{{ $city }}</h3>
                            <p class="text-gray-700 dark:text-gray-300 text-sm">{{ $bookings->count() }} réservation(s) annulée(s)</p>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
            @endif
            @endif
        </div>


        <!-- Modale pour laisser un avis -->
        @if($showReviewModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg w-11/12 md:w-1/2 p-6">
                <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-gray-100">
                    {{ $editReviewId ? 'Modifier votre avis' : 'Laisser un avis' }}
                </h2>
                <form wire:submit.prevent="{{ $editReviewId ? 'updateReview' : 'submitReview' }}">
                    <textarea wire:model="{{ $editReviewId ? 'editReviewContent' : 'review' }}" class="w-full p-2 border rounded-lg mb-4 bg-white dark:bg-gray-900 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" placeholder="Écrivez votre avis ici..."></textarea>
                    @error($editReviewId ? 'editReviewContent' : 'review') <span class="text-red-500">{{ $message }}</span> @enderror

                    <div class="flex items-center space-x-1 mb-4">
                        @for($i = 1; $i <= 5; $i++)
                            <label class="cursor-pointer">
                            <input type="radio" wire:model="{{ $editReviewId ? 'editReviewRating' : 'rating' }}" value="{{ $i }}" class="hidden" />
                            <svg class="w-6 h-6 {{ ($editReviewId ? $editReviewRating : $rating) >= $i ? 'text-yellow-500' : 'text-gray-400 dark:text-gray-500' }} hover:text-yellow-500 transition-colors duration-200" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" />
                            </svg>
                            </label>
                            @endfor
                    </div>
                    @error($editReviewId ? 'editReviewRating' : 'rating') <span class="text-red-500">{{ $message }}</span> @enderror

                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeReviewModal" class="bg-gray-500 hover:bg-gray-600 text-white py-2 px-4 rounded transition">Annuler</button>
                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded transition">
                            {{ $editReviewId ? 'Mettre à jour' : 'Envoyer' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif
    </div>