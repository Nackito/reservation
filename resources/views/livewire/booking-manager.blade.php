<div>
    @php
    $isOccupied = $property && $property->bookings()
    ->where(function($q){
    $q->where('status','accepted')
    ->orWhere('payment_status','paid');
    })
    ->whereDate('start_date', '<=', now())
        ->whereDate('end_date', '>=', now())
        ->exists();

        // Afficher uniquement pour les résidences meublées (catégorie id = 2) – robuste (id ou nom)
        $isResidenceMeublee = false;
        if ($property) {
        $catId = (int) ($property->category_id ?? 0);
        $catName = $property->category->name ?? null;
        $normalized = $catName ? mb_strtolower($catName) : null;
        $isResidenceMeublee = ($catId === 2)
        || ($normalized && in_array($normalized, [
        'résidence meublée',
        'residence meublée',
        'residence meublee',
        'résidence meublee',
        ]));
        }
        @endphp

        @if($isResidenceMeublee && $isOccupied)
        <div class="mb-4 p-3 bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 rounded text-center">
            Ce bien est actuellement <span class="font-semibold">occupé</span>. Vous pouvez essayer de réserver à une autre date.
        </div>
        @endif

        <div class="container mx-auto py-8">
            <input type="hidden" id="dateRangeModel" wire:model.defer="dateRange" />
            @if($isResidenceMeublee)
            <div class="mb-4">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold {{ $isOccupied ? 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                    <i class="fas {{ $isOccupied ? 'fa-lock' : 'fa-unlock' }} mr-2"></i>
                    {{ $isOccupied ? 'Occupé' : 'Disponible' }}
                </span>
            </div>
            @endif

            <form wire:submit.prevent="searchDates" class="mb-4 hidden md:block">
                <div class="flex mt-4 flex-col sm:flex-row gap-2 sm:gap-3 items-center bg-white rounded-lg p-2 dark:bg-gray-800">
                    <div class="w-full" id="DateRange1Wrapper" wire:ignore>
                        <input type="text" id="ReservationDateRange" class="py-3 px-4 block w-full border border-blue-400 bg-white text-gray-900 placeholder-gray-500 rounded-lg text-sm
                focus:border-blue-600 focus:ring-blue-500 disabled:opacity-50
                disabled:pointer-events-none dark:bg-gray-900 dark:border-blue-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:ring-blue-400" placeholder="Choisissez vos dates (arrivée - départ)">
                    </div>
                    @error('dateRange') <span class="text-red-500">{{ $message }}</span> @enderror
                    <div class="flex items-center gap-2">
                        <button type="submit" id="confirm-booking" class="bg-blue-500 text-white py-2 px-4 rounded">
                            Rechercher
                        </button>
                    </div>
                </div>
            </form>
        </div>




        <!-- NavBar -->
        <div class="relative">
            <nav id="menu" class="hidden lg:flex flex-col lg:flex-row justify-center gap-y-2 gap-x-10 bg-white dark:bg-gray-800 py-6 px-8 shadow-lg">
                <a href="#overview" class="nav-link text-lg px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold transition">Vue d'ensemble</a>
                <a href="#pricing" class="nav-link text-lg px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold transition">Tarifs</a>
                <a href="#info" class="nav-link text-lg px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold transition">À savoir</a>
                <a href="#reviews" class="nav-link text-lg px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold transition">Avis des clients</a>
            </nav>
        </div>

        <div class="container bg-white dark:bg-gray-900 mx-auto mt-8">
            <!-- Overview section -->
            <div id="overview" class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden w-61 h-90">
                <div class="flex justify-between items-start pl-4 pt-6 pr-4">
                    <div>
                        <h2 class="text-2xl lg:text-3xl text-gray-800 dark:text-gray-100 font-inter font-extrabold">{{ $property->name ?? 'Nom non disponible' }}</h2>

                        {{-- Note moyenne sous le titre --}}
                        @php
                        $avg = $avgRating ?? null;
                        $count = $approvedReviewsCount ?? 0;
                        $filled = (int) floor($avg ?? 0);
                        $half = ($avg !== null && $avg - $filled >= 0.5) ? 1 : 0;
                        $empty = 5 - $filled - $half;
                        @endphp
                        @if($avg !== null && $count > 0)
                        <div class="flex items-center mt-1" aria-label="Note moyenne {{ $avg }} sur 5">
                            @for($i=0;$i<$filled;$i++)
                                <svg class="w-5 h-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" /></svg>
                                @endfor
                                @if($half)
                                <svg class="w-5 h-5 text-yellow-400" viewBox="0 0 24 24" aria-hidden="true">
                                    <defs>
                                        <linearGradient id="half-booking">
                                            <stop offset="50%" stop-color="currentColor" />
                                            <stop offset="50%" stop-color="transparent" />
                                        </linearGradient>
                                    </defs>
                                    <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" fill="url(#half-booking)" stroke="currentColor" />
                                </svg>
                                @endif
                                @for($i=0;$i<$empty;$i++)
                                    <svg class="w-5 h-5 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" /></svg>
                                    @endfor
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300 font-medium">{{ number_format($avg,1) }} ({{ $count }} avis)</span>
                        </div>
                        @endif

                        {{-- CTA avis contextuel --}}
                        @if(Auth::check())
                        @if($canLeaveReview && !$userHasReview && $eligibleBookingId)
                        <a href="{{ route('user-reservations.review', ['booking' => $eligibleBookingId]) }}" class="inline-flex items-center mt-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded">
                            <i class="fas fa-star mr-2"></i>
                            Laisser un avis
                        </a>
                        @elseif($userHasReview)
                        <a href="{{ route('user-reservations.review', ['booking' => $eligibleBookingId, 'edit' => 1]) }}" class="inline-flex items-center mt-2 px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white text-sm font-medium rounded">
                            <i class="fas fa-edit mr-2"></i>
                            Modifier mon avis
                        </a>
                        @endif
                        @endif

                        <p class="text-lg lg:text-xl text-gray-700 dark:text-gray-300">
                            <a href="#map" title="Voir la carte">
                                <i class="fas fa-map-marker-alt text-blue-500 mr-2 cursor-pointer"></i>
                            </a>
                            {{ $property->city ?? 'Ville non disponible' }}, {{ $property && $property->municipality ? $property->municipality : 'Municipalité non disponible' }}, {{ $property->district ?? 'Quartier non disponible' }}
                        </p>
                    </div>
                    <div class="flex gap-2 mt-1">
                        <!-- Bouton J'aime (wishlist) -->
                        @php
                        $isWished = Auth::check() && $property ? Auth::user()->wishlists()->where('property_id', $property->id)->exists() : false;
                        @endphp
                        <button
                            @if(Auth::check())
                            wire:click="toggleWishlist"
                            @else
                            onclick="showLoginNotification()"
                            @endif
                            type="button"
                            class="flex items-center justify-center px-3 py-2 {{ $isWished ? 'bg-pink-500 text-white' : 'bg-pink-100 text-pink-600' }} hover:bg-pink-200 rounded-lg shadow transition"
                            title="{{ Auth::check() ? ($isWished ? 'Retirer de ma liste de souhait' : 'Ajouter à ma liste de souhait') : 'Connectez-vous pour ajouter à votre liste de souhait' }}"
                            @if(!$property) disabled @endif>
                            <i class="fas fa-heart {{ $isWished ? '' : 'text-pink-600' }} text-lg"></i>
                            <span class="hidden sm:inline ml-1">{{ $isWished ? 'Retirer' : "J'aime" }}</span>
                        </button>
                        <script>
                            function showContactLoginNotification() {
                                if (window.Swal) {
                                    Swal.fire({
                                        title: 'Connexion requise',
                                        text: 'Vous devez être connecté pour envoyer un message. Voulez-vous vous connecter maintenant ?',
                                        icon: 'info',
                                        showCancelButton: true,
                                        confirmButtonText: 'Se connecter',
                                        cancelButtonText: 'Annuler',
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = '/login';
                                        }
                                    });
                                } else {
                                    if (confirm('Vous devez être connecté pour envoyer un message. Voulez-vous vous connecter maintenant ?')) {
                                        window.location.href = '/login';
                                    }
                                }
                            }

                            function showReservationLoginNotification() {
                                if (window.Swal) {
                                    Swal.fire({
                                        title: 'Connexion requise',
                                        text: 'Vous devez être connecté pour effectuer une réservation. Voulez-vous vous connecter maintenant ?',
                                        icon: 'info',
                                        showCancelButton: true,
                                        confirmButtonText: 'Se connecter',
                                        cancelButtonText: 'Annuler',
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = '/login';
                                        }
                                    });
                                } else {
                                    if (confirm('Vous devez être connecté pour envoyer un message. Voulez-vous vous connecter maintenant ?')) {
                                        window.location.href = '/login';
                                    }
                                }
                            }

                            function showLoginNotification() {
                                if (window.Swal) {
                                    Swal.fire({
                                        title: 'Connexion requise',
                                        text: 'Vous devez être connecté pour ajouter un établissement à votre liste de souhait. Voulez-vous vous connecter maintenant ?',
                                        icon: 'info',
                                        showCancelButton: true,
                                        confirmButtonText: 'Se connecter',
                                        cancelButtonText: 'Annuler',
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = '/login';
                                        }
                                    });
                                } else {
                                    if (confirm('Vous devez être connecté pour ajouter un établissement à votre liste de souhait. Voulez-vous vous connecter maintenant ?')) {
                                        window.location.href = '/login';
                                    }
                                }
                            }
                        </script>
                        <!-- Bouton de partage -->
                        <button onclick="shareProperty()" class="flex items-center justify-center px-3 py-2 bg-blue-100 hover:bg-blue-200 text-blue-600 rounded-lg shadow transition" title="Partager">
                            <i class="fas fa-share-alt text-lg"></i>
                            <span class="hidden sm:inline ml-1">Partager</span>
                        </button>
                        <!-- Modal de partage -->
                        <div id="shareModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-60 flex items-center justify-center">
                            <div class="bg-white rounded-lg shadow-lg w-full max-w-sm p-6 relative">
                                <button class="absolute top-2 right-2 text-gray-400 hover:text-gray-700" onclick="closeShareModal()">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                                <h2 class="text-xl font-bold mb-4 text-gray-800">Partager cette page</h2>
                                <div class="flex flex-col gap-3">
                                    <button onclick="copyShareLink()" class="flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700">
                                        <i class="fas fa-link mr-2"></i> Copier le lien
                                    </button>
                                    <a href="#" onclick="shareWhatsapp(event)" class="flex items-center px-4 py-2 bg-green-100 hover:bg-green-200 rounded-lg text-green-700">
                                        <i class="fab fa-whatsapp mr-2"></i> Partager sur WhatsApp
                                    </a>
                                    <a href="#" onclick="shareFacebook(event)" class="flex items-center px-4 py-2 bg-blue-100 hover:bg-blue-200 rounded-lg text-blue-700">
                                        <i class="fab fa-facebook mr-2"></i> Partager sur Facebook
                                    </a>
                                    <a href="#" onclick="shareInstagram(event)" class="flex items-center px-4 py-2 bg-pink-100 hover:bg-pink-200 rounded-lg text-pink-600">
                                        <i class="fab fa-instagram mr-2"></i> Partager sur Instagram
                                    </a>
                                </div>
                            </div>
                        </div>
                        <!-- Bouton de contact Afridayz (connexion requise) -->
                        @if($property)
                        @if(Auth::check())
                        <button type="button" wire:click.prevent="contactAfridayz" class="flex items-center justify-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg shadow transition" title="Contacter un employé de la plateforme">
                            <i class="fas fa-headset text-lg"></i>
                            <span class="hidden sm:inline ml-1">Contacter Afridayz</span>
                        </button>
                        @else
                        <button onclick="showContactLoginNotification()" class="flex items-center justify-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg shadow transition" title="Connectez-vous pour contacter un employé">
                            <i class="fas fa-headset text-lg"></i>
                            <span class="hidden sm:inline ml-1">Contacter Afridayz</span>
                        </button>
                        @endif
                        @endif
                        <!-- Modal de contact employé -->
                        <div id="contactEmployeeModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-60 flex items-center justify-center">
                            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
                                <button class="absolute top-2 right-2 text-gray-400 hover:text-gray-700" onclick="closeContactModal()">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                                <h2 class="text-2xl font-bold mb-4 text-gray-800">Contacter Afridayz</h2>
                                <form method="POST" action="{{ route('contact.hebergement') }}">
                                    @csrf
                                    <div class="mb-4">
                                        <label for="message" class="block text-gray-700 font-semibold mb-2">Votre message</label>
                                        <textarea id="message" name="message" rows="4" class="w-full border rounded p-2" required></textarea>
                                    </div>
                                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">Envoyer</button>
                                </form>
                            </div>
                        </div>
                        <script>
                            function openContactModal() {
                                document.getElementById('contactEmployeeModal').classList.remove('hidden');
                            }

                            function closeContactModal() {
                                document.getElementById('contactEmployeeModal').classList.add('hidden');
                            }
                        </script>
                    </div>

                </div>
            </div>

            <div class="container mx-auto mt-8 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Section des images (2/3) -->
                <div id="PropertyImage" class="lg:col-span-2 pl-4 pr-4">
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @if($property && $property->images)
                        @foreach($property->images as $index => $image)
                        @if ($index < 3)
                            <div class="image-container relative {{ $index % 3 === 0 ? 'large' : 'small' }}">
                            @php
                            $imgPath = $image->image_path ?? '';
                            if (\Illuminate\Support\Str::startsWith($imgPath, 'storage/')) {
                            $imgPath = substr($imgPath, 8);
                            }
                            $imgPath = ltrim($imgPath, '/');
                            @endphp
                            <img src="{{ asset('storage/' . $imgPath) }}" alt="Image de la propriété" class="w-full h-auto object-cover rounded-lg cursor-pointer" onclick="openGallery({{ $index }})">
                            @if($index === 2 && $property->images->count() > 3)
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white text-lg font-bold cursor-pointer rounded-lg" onclick="openGallery({{ $index }})">
                                +{{ $property->images->count() - 3 }}
                            </div>
                            @endif
                    </div>
                    @endif
                    @endforeach
                    @endif
                </div>
            </div>

            <!-- Section "House rules" (1/3) -->
            <div class="p-4 flex flex-col justify-between h-full">
                <div id="house-rules" class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-4 ">
                    <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100">Les équipements de l'établissement</h2>
                    <ul class="list-none mt-4 text-gray-600 dark:text-gray-300">
                        @forelse($property->features as $feature)
                        <li class="flex items-center mb-2">
                            @php
                            // Utilise le mapping normalisé défini dans le composant Livewire
                            $iconClass = method_exists($this, 'iconClassForFeature')
                            ? $this->iconClassForFeature($feature)
                            : ($featureIcons[$feature] ?? 'fa-circle');
                            @endphp
                            <i class="fas {{ $iconClass }} text-blue-500 mr-2"></i>
                            <span>{{ $feature }}</span>
                        </li>
                        @empty
                        <li>Aucun équipement disponible pour cet établissement.</li>
                        @endforelse
                    </ul>
                    <div class="p-4">
                        @php
                        $user = auth()->user();
                        $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                        $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
                        $basePrice = $property->starting_price ?? $property->price_per_night; // Utiliser le prix de départ pour les hôtels
                        $displayCurrency = ($rate && $rate > 0) ? $userCurrency : 'XOF';
                        $converted = ($rate && $rate > 0 && $basePrice !== null) ? round($basePrice * $rate, 2) : $basePrice;
                        $isHotel = $property && $property->category && in_array($property->category->name, ['Hôtel','Hotel']);
                        @endphp
                        @if($basePrice !== null)
                        <p class="text-gray-600 dark:text-gray-200 text-right font-bold mt-5">
                            @if($isHotel)
                            À partir de {{ number_format($converted, 2) }} {{ $displayCurrency }} par nuit
                            @else
                            {{ number_format($converted, 2) }} {{ $displayCurrency }} par nuit
                            @endif
                        </p>
                        @endif
                        <div class="mt-4">
                            <a href="#Reservation" class="border border-blue-500 bg-white-500 text-blue-500 text-center py-2 px-4 rounded block w-full">Réserver</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4">
            <div class="prose dark:prose-invert max-w-none text-gray-800 dark:text-gray-100 mt-5">
                {!! $property->description ?? 'Description non disponible' !!}
            </div>
            <p id="pricing" class="text-gray-600 dark:text-gray-200 mt-5">
                @php
                $user = auth()->user();
                $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
                $basePrice = $property->starting_price ?? $property->price_per_night; // starting_price pour hôtel
                $displayCurrency = ($rate && $rate > 0) ? $userCurrency : 'XOF';
                $converted = ($rate && $rate > 0 && $basePrice !== null) ? round($basePrice * $rate, 2) : $basePrice;
                $isHotel = $property && $property->category && in_array($property->category->name, ['Hôtel','Hotel']);
                @endphp
                @if($basePrice !== null)
                @if($isHotel)
                À partir de <span class="text-xl font-bold">{{ number_format($converted, 2) }} {{ $displayCurrency }} par nuit</span>
                @else
                Vous pouvez disposez de ce logement à <span class="text-xl font-bold">{{ number_format($converted, 2) }} {{ $displayCurrency }} par nuit</span>
                @endif
                @endif
            </p>
        </div>


        @if(!is_null($property->latitude) && !is_null($property->longitude))
        <h2 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mt-8 mb-4 pl-4">Emplacement de l'établissement</h2>
        <div id="map" wire:ignore
            data-lat="{{ $property->latitude }}"
            data-lng="{{ $property->longitude }}"
            data-label="{{ $property->name ?? 'Résidence' }}">
        </div>
        <script>
            (function() {
                function initPropertyMap() {
                    try {
                        var el = document.getElementById('map');
                        if (!el) return;
                        // Empêcher des ré-initialisations multiples sur le même élément
                        if (el.dataset && el.dataset.inited === '1') return;
                        var lat = parseFloat(el.dataset.lat);
                        var lng = parseFloat(el.dataset.lng);
                        if (!isFinite(lat) || !isFinite(lng)) return;
                        var label = el.dataset.label || 'Résidence';
                        if (typeof window.init === 'function') {
                            window.init(lat, lng, label);
                            if (el && el.dataset) el.dataset.inited = '1';
                        }
                    } catch (e) {
                        // ignore
                    }
                }
                window.initPropertyMap = initPropertyMap;
                document.addEventListener('DOMContentLoaded', initPropertyMap);
                document.addEventListener('livewire:load', () => setTimeout(initPropertyMap, 0));
                document.addEventListener('livewire:navigated', () => setTimeout(initPropertyMap, 0));
            })();
        </script>
        @endif
</div>

<!-- Info section -->
<div class="container bg-white dark:bg-gray-900 mx-auto mt-8">
    <div id="info" class="text-gray-500 dark:text-gray-300 mt-5 bg-white dark:bg-gray-800 shadow-md rounded-lg p-4">
        <p class="p-4">
            Vous devrez présenter une pièce d'identité avec photo lors de le remise des clés. Veuillez noter que toutes les demandes spéciales seront satisfaites sous réserve de disponibilité et pourront entraîner des frais supplémentaires.
        </p>
        <p class="p-4">
            <i class="fas fa-sign-in-alt text-blue-500 mr-2"></i> <!-- Icône pour l'arrivée -->
            Arrivée : 15h00 - 20h00
        </p>
        <p class="p-4">
            <i class="fas fa-sign-out-alt text-blue-500 mr-2"></i> <!-- Icône pour le départ -->
            Départ : 10h00 - 12h00
        </p>
        <p class="p-4">
            Politique d'annulation : Vous pouvez annuler gratuitement jusqu'à 24 heures avant votre arrivée. Passé ce délai, des frais d'annulation de 50% seront appliqués.
        </p>
        <p class="p-4">
            Politique de remboursement : En cas d'annulation dans les 24 heures précédant votre arrivée, le montant total de la réservation sera facturé.
        </p>
    </div>
</div>

<!--2nd Reservation form -->
<div class="container mx-auto mt-8">
    <h1 class="block text-3xl font-bold text-gray-800 dark:text-gray-100 sm:text-4xl lg:text-2xl lg:leading-tight mt-6 mb-4 sm:mt-0 sm:mb-6 px-4 sm:px-0">Entrez vos dates</h1>

    {{-- Champ caché déplacé plus haut pour couvrir les 2 formulaires --}}

    <form wire:submit.prevent="searchDates" class="mb-4" id="Reservation">
        <div class="flex mt-4 flex-col sm:flex-row gap-2 sm:gap-3 items-center bg-white rounded-lg p-2 dark:bg-gray-800 custom-mobile-reservation-form">

            <div class="w-full">
                <input type="text" value="{{ $property->name ?? '' }}" readonly
                    class="py-3 px-4 block w-full border border-blue-400 bg-white text-gray-900 placeholder-gray-500 rounded-lg text-lg font-bold shadow-sm
                    focus:border-blue-600 focus:ring-blue-500 disabled:opacity-50
                    disabled:pointer-events-none dark:bg-gray-900 dark:border-blue-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:ring-blue-400"
                    placeholder="Nom de l'établissement">
            </div>

            {{-- Champ type de chambre supprimé: le choix se fait via le bouton Réserver du tableau --}}
            <div class="w-full" id="DateRange2Wrapper" wire:ignore>
                <input type="text" id="ReservationDateRange2" class="py-3 px-4 block w-full border border-blue-400 bg-white text-gray-900 placeholder-gray-500 rounded-lg text-sm cursor-pointer
                    focus:border-blue-600 focus:ring-blue-500 disabled:opacity-50
                    disabled:pointer-events-none dark:bg-gray-900 dark:border-blue-700 dark:text-gray-100 dark:placeholder-gray-400 dark:focus:ring-blue-400" placeholder="Choisissez vos dates (arrivée - départ)">
            </div>
            @error('dateRange') <span class="text-red-500">{{ $message }}</span> @enderror

            <button type="submit" id="confirm-booking" class="bg-blue-500 text-white py-2 px-4 rounded">
                Rechercher
            </button>
        </div>

        <script>
            // ===== Paramètres Flatpickr injectés depuis la config (base de données / config/app.php) =====
            window.FP_CONFIG = {
                dateFormat: @json(config('app.flatpickr_date_format', 'Y-m-d')),
                altFormat: @json(config('app.flatpickr_alt_format', 'j F Y')),
                rangeSeparator: @json(config('app.flatpickr_range_separator', ' au ')),
                locale: @json(config('app.flatpickr_locale', 'fr'))
            };
            // ===== Récupération de la plage depuis Livewire/DB uniquement (pas d'URL par défaut) =====
            function normalizeRange(val) {
                if (!val) return '';
                return String(val)
                    .replace(/\s+à\s+/gi, ' to ')
                    .replace(/\s+au\s+/gi, ' to ')
                    .replace(/\s+–\s+/g, ' to ')
                    .replace(/\s+—\s+/g, ' to ')
                    .replace(/\s+-\s+/g, ' to ')
                    .replace(/\s+to\s+/gi, ' to ')
                    .trim();
            }

            function parseToArray(isoRange) {
                const v = normalizeRange(isoRange);
                const parts = v.split(' to ').map(s => s && s.trim()).filter(Boolean);
                return parts.length === 2 ? parts : null;
            }

            function getUrlDateRange() {
                return '';
            }

            function getStoredJsonRange() {
                return '';
            }

            // getTodayTomorrow supprimé: plus de valeur par défaut auto

            function applyRangeToInputs(isoRange) {
                if (!isoRange) return;
                const el1 = document.getElementById('ReservationDateRange');
                const el2 = document.getElementById('ReservationDateRange2');
                const model = document.getElementById('dateRangeModel');
                const parts = parseToArray(isoRange);
                // Mettre la valeur dans les inputs visibles et déclencher un événement input pour Livewire
                [el1, el2].forEach((el) => {
                    if (!el) return;
                    try {
                        // Verrou pour éviter les boucles avec onChange Flatpickr
                        window._rangeUpdateLock = (window._rangeUpdateLock || 0) + 1;
                        if (el._flatpickr && parts) {
                            try {
                                el._flatpickr.setDate(parts, true);
                                // Remplacer l’affichage "to" par " au " dans l’alt input
                                const alt = el._flatpickr.altInput;
                                if (alt && typeof alt.value === 'string') {
                                    const sep = (window.FP_CONFIG && window.FP_CONFIG.rangeSeparator) || ' au ';
                                    alt.value = alt.value.replace(' to ', sep);
                                }
                            } catch (_) {}
                        } else {
                            // Fallback sans flatpickr: écrire l'ISO (Livewire gardera la valeur)
                            el.value = isoRange;
                            el.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                        }
                    } catch (_) {} finally {
                        window._rangeUpdateLock = Math.max(0, (window._rangeUpdateLock || 1) - 1);
                    }
                });
                // Mettre à jour le modèle Livewire caché
                if (model) {
                    model.value = isoRange;
                    model.dispatchEvent(new Event('input', {
                        bubbles: true
                    }));
                    model.dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                }
            }

            // ===== Helpers d'enregistrement JSON (persistance inverse) =====
            function isoToHuman(isoRange) {
                const parts = parseToArray(isoRange);
                if (!parts) return '';
                const toHuman = (s) => {
                    const [y, m, d] = String(s).split('-');
                    if (!y || !m || !d) return s;
                    return `${d.padStart(2,'0')}/${m.padStart(2,'0')}/${y}`;
                };
                return `${toHuman(parts[0])} au ${toHuman(parts[1])}`;
            }

            function saveSearchDateRangeJson(isoRange) {
                const norm = normalizeRange(isoRange);
                const parts = parseToArray(norm);
                if (!parts) return;
                const obj = {
                    start: parts[0],
                    end: parts[1],
                    iso: `${parts[0]} to ${parts[1]}`,
                    human: isoToHuman(`${parts[0]} to ${parts[1]}`),
                    updatedAt: new Date().toISOString()
                };
                try {
                    localStorage.setItem('search.dateRange.json', JSON.stringify(obj));
                    // Compat legacy
                    localStorage.setItem('booking.dateRange', obj.iso);
                    localStorage.setItem('booking.checkIn', obj.start);
                    localStorage.setItem('booking.checkOut', obj.end);
                    // Expose global + événement
                    window.searchDateRange = obj;
                    window.dispatchEvent(new CustomEvent('search-date-range-updated', {
                        detail: obj
                    }));
                } catch (_) {}
            }

            function bindRangePersistenceListeners() {
                const el1 = document.getElementById('ReservationDateRange');
                const el2 = document.getElementById('ReservationDateRange2');
                const bindFor = (el) => {
                    if (!el || el.dataset.rangeSaveBound === '1') return;
                    el.dataset.rangeSaveBound = '1';
                    // Sauvegarde lors de la saisie utilisateur uniquement (événements trusted)
                    const onUserInput = (e) => {
                        if (!e || e.isTrusted !== true) return; // ignorer événements synthétiques
                        const v = normalizeRange(el.value);
                        const parts = parseToArray(v);
                        if (parts) {
                            saveSearchDateRangeJson(v);
                            // Garder les deux inputs synchronisés
                            applyRangeToInputs(v);
                        }
                    };
                    el.addEventListener('change', onUserInput);
                    el.addEventListener('input', onUserInput);
                };
                bindFor(el1);
                bindFor(el2);
            }

            // Indicateur global d'authentification pour choisir la persistance côté serveur
            window.IS_AUTH = !!(@json(Auth::check()));

            // Options Flatpickr partagées pour garantir une configuration unique partout
            window.getFlatpickrBaseOptions = function() {
                return {
                    mode: 'range',
                    dateFormat: (window.FP_CONFIG && window.FP_CONFIG.dateFormat) || 'Y-m-d',
                    minDate: 'today',
                    // Ne pas désactiver les jours: sélection toujours possible
                    disable: [],
                    altInput: true,
                    altFormat: (window.FP_CONFIG && window.FP_CONFIG.altFormat) || 'j F Y',
                    rangeSeparator: (window.FP_CONFIG && window.FP_CONFIG.rangeSeparator) || ' au ',
                    locale: (function() {
                        const pref = (window.FP_CONFIG && window.FP_CONFIG.locale) || 'fr';
                        if (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns[pref]) return window.flatpickr.l10ns[pref];
                        return 'default';
                    })(),
                    allowInput: false,
                    onReady: function(selectedDates, dateStr, inst) {
                        // Harmoniser le séparateur visuel
                        const alt = inst && inst.altInput;
                        if (alt && typeof alt.value === 'string') alt.value = alt.value.replace(' to ', ((window.FP_CONFIG && window.FP_CONFIG.rangeSeparator) || ' au '));
                    },
                    onOpen: function(selectedDates, dateStr, inst) {
                        const alt = inst && inst.altInput;
                        if (alt && typeof alt.value === 'string') alt.value = alt.value.replace(' to ', ((window.FP_CONFIG && window.FP_CONFIG.rangeSeparator) || ' au '));
                    },
                    onChange: function(selectedDates, dateStr, instance) {
                        try {
                            // Normaliser la chaîne retournée par Flatpickr (format ISO attendu)
                            const norm = normalizeRange(dateStr || '');
                            const parts = parseToArray(norm);
                            if (!parts) return;

                            const iso = `${parts[0]} to ${parts[1]}`;

                            // Mettre à jour les inputs visibles et le modèle Livewire caché
                            applyRangeToInputs(iso);

                            // Harmoniser le séparateur visuel (altInput)
                            const alt = instance && instance.altInput;
                            if (alt && typeof alt.value === 'string') {
                                alt.value = alt.value.replace(' to ', ((window.FP_CONFIG && window.FP_CONFIG.rangeSeparator) || ' au '));
                            }

                            // Persister immédiatement côté serveur via Livewire
                            function getComponentId() {
                                try {
                                    const model = document.getElementById('dateRangeModel');
                                    if (model) {
                                        const root = model.closest('[wire\\:id]');
                                        if (root) return root.getAttribute('wire:id');
                                    }
                                } catch (_) {}
                                const anyRoot = document.querySelector('[wire\\:id]');
                                return anyRoot ? anyRoot.getAttribute('wire:id') : null;
                            }
                            if (window.Livewire && typeof window.Livewire.find === 'function') {
                                const id = getComponentId();
                                const cmp = id ? window.Livewire.find(id) : null;
                                if (cmp) {
                                    if (typeof cmp.set === 'function') cmp.set('dateRange', iso);
                                    if (typeof cmp.call === 'function') {
                                        // Invoker session-only si non authentifié; sinon, upsert standard
                                        if (window.IS_AUTH === false) {
                                            cmp.call('persistDateRangeBySession', iso);
                                        } else {
                                            cmp.call('persistDateRange', iso);
                                        }
                                    }
                                }
                            }
                        } catch (_) {}
                    }
                };
            };
            // (Ré)initialise Flatpickr sur les champs de date si nécessaire
            window.ensureInitDatePickers = function() {
                // Helper: récupère la valeur initiale de Livewire si présente
                function getLivewireInitialRange() {
                    try {
                        // Priorité: modèle caché Livewire
                        const model = document.getElementById('dateRangeModel');
                        if (model && model.value) {
                            const v = normalizeRange(model.value);
                            const p = parseToArray(v);
                            if (p) return `${p[0]} to ${p[1]}`;
                        }
                        // Sinon inputs visibles
                        const el1 = document.getElementById('ReservationDateRange');
                        const el2 = document.getElementById('ReservationDateRange2');
                        const raw = (el1 && el1.value ? el1.value : '') || (el2 && el2.value ? el2.value : '');
                        const norm = normalizeRange(raw);
                        const parts = parseToArray(norm);
                        return parts ? `${parts[0]} to ${parts[1]}` : '';
                    } catch (_) {
                        return '';
                    }
                }
                const baseOpts = (typeof window.getFlatpickrBaseOptions === 'function') ? window.getFlatpickrBaseOptions() : {};
                const el1 = document.getElementById('ReservationDateRange');
                const el2 = document.getElementById('ReservationDateRange2');
                // Priorité: Livewire (côté serveur) > URL > défaut (aujourd'hui → demain)
                // NB: on n'utilise PAS localStorage pour l'initialisation afin d'éviter des dates obsolètes
                let initial = getLivewireInitialRange() || getUrlDateRange() || '';
                if (typeof window.flatpickr !== 'function') {
                    // Flatpickr non dispo: n'applique rien si aucune valeur initiale
                    if (initial) applyRangeToInputs(initial);
                    // Binder la persistance sur saisie classique
                    bindRangePersistenceListeners();
                    return;
                }
                if (el1 && !el1._flatpickr) {
                    try {
                        const w1 = document.getElementById('DateRange1Wrapper') || el1.parentElement;
                        const opts1 = Object.assign({}, baseOpts, {
                            appendTo: w1,
                            positionElement: el1
                        });
                        const inst = window.flatpickr(el1, opts1);
                        const setInit = initial; // pas de défaut auto
                        const arr = parseToArray(setInit);
                        if (arr) {
                            try {
                                inst.setDate(arr, true);
                            } catch (_) {}
                        }
                        const alt = inst && inst.altInput;
                        if (alt && typeof alt.value === 'string') alt.value = alt.value.replace(' to ', ((window.FP_CONFIG && window.FP_CONFIG.rangeSeparator) || ' au '));
                    } catch (_) {}
                }
                if (el2 && !el2._flatpickr) {
                    try {
                        const w2 = document.getElementById('DateRange2Wrapper') || el2.parentElement;
                        const opts2 = Object.assign({}, baseOpts, {
                            appendTo: w2,
                            positionElement: el2
                        });
                        const inst2 = window.flatpickr(el2, opts2);
                        const setInit2 = initial; // pas de défaut auto
                        const arr2 = parseToArray(setInit2);
                        if (arr2) {
                            try {
                                inst2.setDate(arr2, true);
                            } catch (_) {}
                        }
                        const alt2 = inst2 && inst2.altInput;
                        if (alt2 && typeof alt2.value === 'string') alt2.value = alt2.value.replace(' to ', ((window.FP_CONFIG && window.FP_CONFIG.rangeSeparator) || ' au '));
                    } catch (_) {}
                }
                // S'assurer que la valeur Livewire reflète la plage choisie
                if (initial) applyRangeToInputs(initial);
                // Binder la persistance après init
                bindRangePersistenceListeners();
                // Écouter l'événement Livewire pour réappliquer la plage en FR dans Flatpickr (une seule fois)
                if (!window.__dateRangeUpdatedListenerBound) {
                    document.addEventListener('date-range-updated', (e) => {
                        try {
                            const iso = e && e.detail && e.detail.dateRange ? String(e.detail.dateRange) : '';
                            if (!iso) return;
                            applyRangeToInputs(iso);
                        } catch (_) {}
                    });
                    window.__dateRangeUpdatedListenerBound = true;
                }
                // Re-application après chaque mise à jour Livewire: recharger depuis le modèle caché
                try {
                    if (window.Livewire && typeof window.Livewire.hook === 'function') {
                        window.Livewire.hook('message.processed', () => {
                            setTimeout(() => {
                                try {
                                    const model = document.getElementById('dateRangeModel');
                                    const iso = model && model.value ? normalizeRange(model.value) : '';
                                    if (iso) applyRangeToInputs(iso); // sinon, ne réinjecte rien
                                } catch (_) {}
                            }, 0);
                        });
                    }
                } catch (_) {}
            };
            // Nettoyage: suppression des fonctions de coloration des dates occupées

            // Rafraîchir après chargement Livewire et navigation Livewire
            document.addEventListener('livewire:load', () => setTimeout(() => {
                window.ensureInitDatePickers();
                if (typeof bindRangePersistenceListeners === 'function') bindRangePersistenceListeners();
            }, 0));
            document.addEventListener('livewire:navigated', () => setTimeout(() => {
                window.ensureInitDatePickers();
                if (typeof bindRangePersistenceListeners === 'function') bindRangePersistenceListeners();
            }, 0));
            // L'événement occupied-dates-updated n'a plus d'effet (coloration retirée)

            // Survol des lignes: supprimé (plus de changement visuel par type)

            // Ouverture automatique du calendrier après changement de chambre (avec scroll)
            window._calendarOpenTarget = null;
            window.openCalendarForInput = function(inputEl) {
                if (!inputEl) return;
                try {
                    inputEl.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                } catch (_) {}
                const tryOpen = () => {
                    if (inputEl._flatpickr) {
                        try {
                            inputEl._flatpickr.open();
                        } catch (_) {}
                        return true;
                    }
                    return false;
                };
                if (!tryOpen() && typeof window.flatpickr === 'function') {
                    try {
                        const base = (typeof window.getFlatpickrBaseOptions === 'function') ? window.getFlatpickrBaseOptions() : {
                            mode: 'range',
                            dateFormat: (window.FP_CONFIG && window.FP_CONFIG.dateFormat) || 'Y-m-d',
                            minDate: 'today',
                            disable: [],
                            allowInput: false
                        };
                        const opts = Object.assign({}, base, {
                            appendTo: inputEl.parentElement,
                            positionElement: inputEl
                        });
                        window.flatpickr(inputEl, opts);
                        setTimeout(() => {
                            tryOpen();
                        }, 0);
                    } catch (_) {}
                }
            };

            // Synchroniser le champ caché avant chaque soumission Livewire
            (function() {
                function pad(n) {
                    return String(n).padStart(2, '0');
                }

                function toIso(d) {
                    return d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
                }

                function syncHiddenFromPickers(formEl) {
                    try {
                        const el1 = document.getElementById('ReservationDateRange'); // desktop
                        const el2 = document.getElementById('ReservationDateRange2'); // mobile
                        const model = document.getElementById('dateRangeModel');
                        if (!model) return;
                        // Déterminer l'input prioritaire en fonction du formulaire soumis
                        let primary = null;
                        if (formEl && typeof formEl.querySelector === 'function') {
                            primary = formEl.querySelector('#ReservationDateRange2') || formEl.querySelector('#ReservationDateRange');
                        }
                        const candidates = [];
                        if (primary) candidates.push(primary);
                        // Ajouter l'autre input comme fallback
                        if (primary === el2 && el1) candidates.push(el1);
                        if (primary === el1 && el2) candidates.push(el2);
                        if (!primary) {
                            // Fallback: mobile d'abord si visible, sinon desktop
                            if (el2) candidates.push(el2);
                            if (el1) candidates.push(el1);
                        }
                        let iso = '';
                        // 1) Essayer de lire via Flatpickr de l'input prioritaire
                        for (const el of candidates) {
                            if (el && el._flatpickr && Array.isArray(el._flatpickr.selectedDates) && el._flatpickr.selectedDates.length === 2) {
                                iso = toIso(el._flatpickr.selectedDates[0]) + ' to ' + toIso(el._flatpickr.selectedDates[1]);
                                break;
                            }
                        }
                        // 2) Fallback: valeur brute de l'input prioritaire (ou des suivants)
                        if (!iso) {
                            for (const el of candidates) {
                                if (el && el.value) {
                                    const norm = normalizeRange(el.value);
                                    const parts = parseToArray(norm);
                                    if (parts) {
                                        iso = parts[0] + ' to ' + parts[1];
                                        break;
                                    }
                                }
                            }
                        }
                        if (iso) {
                            model.value = iso;
                            model.dispatchEvent(new Event('input', {
                                bubbles: true
                            }));
                            model.dispatchEvent(new Event('change', {
                                bubbles: true
                            }));
                        }
                    } catch (_) {}
                }
                document.addEventListener('submit', function(e) {
                    const tgt = e.target;
                    if (!tgt) return;
                    const isLW = tgt.matches('form[wire\\:submit\\.prevent="searchDates"], form[wire\\:submit\\.prevent="addBooking"], form[wire\\:submit\\.prevent="quickReserve"]');
                    if (isLW) {
                        syncHiddenFromPickers(tgt);
                    }
                }, true);
            })();

            window.bindRoomTypeSelectOpenCalendar = function() {
                const selects = document.querySelectorAll('select[wire\\:model="selectedRoomTypeId"]');
                selects.forEach((sel) => {
                    if (sel.dataset.openBound === '1') return;
                    sel.dataset.openBound = '1';
                    sel.addEventListener('change', () => {
                        if (!sel.value) return;
                        // cibler l'input de date dans le même formulaire
                        let target = null;
                        const form = sel.closest('form');
                        if (form) target = form.querySelector('input[id^="ReservationDateRange"]');
                        if (!target) target = (window.innerWidth < 768) ? document.getElementById('ReservationDateRange2') : document.getElementById('ReservationDateRange');
                        window._calendarOpenTarget = target;
                        // sécurité: si l'événement n'arrive pas, ouvrir quand même
                        setTimeout(() => {
                            if (window._calendarOpenTarget) {
                                window.openCalendarForInput(window._calendarOpenTarget);
                                window._calendarOpenTarget = null;
                            }
                        }, 600);
                    });
                });
            };

            // Lier le clic sur les inputs de date pour ouvrir Flatpickr à coup sûr
            window.bindDateInputsClickOpen = function() {
                const inputs = [
                    document.getElementById('ReservationDateRange'),
                    document.getElementById('ReservationDateRange2')
                ].filter(Boolean);
                inputs.forEach((input) => {
                    if (input.dataset.clickOpenBound === '1') return;
                    input.dataset.clickOpenBound = '1';
                    input.addEventListener('click', (e) => {
                        e.stopPropagation();
                        if (typeof window.openCalendarForInput === 'function') {
                            window.openCalendarForInput(input);
                        }
                    });
                    // Sur focus clavier aussi
                    input.addEventListener('focus', () => {
                        if (typeof window.openCalendarForInput === 'function') {
                            window.openCalendarForInput(input);
                        }
                    });
                });
            };

            // Lier au chargement et re-navigation Livewire
            document.addEventListener('livewire:load', () => setTimeout(() => {
                window.bindRoomTypeSelectOpenCalendar();
                window.bindDateInputsClickOpen();
            }, 0));
            document.addEventListener('livewire:navigated', () => setTimeout(() => {
                window.bindRoomTypeSelectOpenCalendar();
                window.bindDateInputsClickOpen();
            }, 0));
            // Après mise à jour des dates occupées, ouvrir l'input ciblé si nécessaire
            // occupied-dates-updated: plus de traitement nécessaire

            // Toast de confirmation après recherche
            document.addEventListener('dates-search-completed', () => {
                try {
                    if (window.Swal && typeof window.Swal.fire === 'function') {
                        window.Swal.fire({
                            toast: true,
                            position: 'top-end',
                            icon: 'success',
                            title: 'Dates mises à jour',
                            showConfirmButton: false,
                            timer: 1800
                        });
                    } else {
                        // Fallback
                        console.log('Dates mises à jour');
                    }
                    // Réappliquer immédiatement la plage depuis le modèle caché
                    try {
                        const model = document.getElementById('dateRangeModel');
                        const iso = model && model.value ? normalizeRange(model.value) : '';
                        if (iso) applyRangeToInputs(iso);
                    } catch (_) {}

                    // Faire défiler vers la section résultats (Disponibilité)
                    try {
                        const target = document.getElementById('availability');
                        if (target && typeof target.scrollIntoView === 'function') {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'center'
                            });
                        }
                    } catch (_) {}
                } catch (_) {}
            });

            // Repropager l'événement Livewire 'date-range-updated' vers un CustomEvent DOM
            document.addEventListener('livewire:load', () => {
                try {
                    if (window.Livewire && typeof window.Livewire.on === 'function') {
                        window.Livewire.on('date-range-updated', (dateRange) => {
                            try {
                                document.dispatchEvent(new CustomEvent('date-range-updated', {
                                    detail: {
                                        dateRange
                                    }
                                }));
                            } catch (_) {}
                        });
                    }
                } catch (_) {}
            });

            // Appel initial après ce rendu
            window.ensureInitDatePickers && window.ensureInitDatePickers();
        </script>
    </form>
</div>

{{-- Tableau des types de chambre (toutes catégories si roomTypes présents) --}}
@if($property && $property->roomTypes && $property->roomTypes->count())
<div class="container mx-auto mt-8 px-4">
    <style>
        /* Décoration des jours occupés retirée à la demande */
    </style>
    @php
    $user = auth()->user();
    $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
    $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
    $displayCurrency = ($rate && $rate > 0) ? $userCurrency : 'XOF';
    @endphp
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Type de chambre</th>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Nombre de personnes</th>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Lits</th>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Prix</th>
                    <th id="availability" scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Disponibilité</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($property->roomTypes as $rt)
                @php
                $rtBasePrice = $rt->price_per_night;
                $rtConverted = ($rate && $rate > 0 && $rtBasePrice !== null) ? round($rtBasePrice * $rate, 2) : $rtBasePrice;
                // Disponibilité selon la plage de dates sélectionnée
                $availabilityLabel = null;
                $availableQty = null;
                // Calculer la dispo pour toutes les chambres si une plage de dates est saisie
                // - inventory = null est traité comme 1 (une chambre de ce type)
                // - inventory = 0 => complet
                if (!empty($dateRange)) {
                $parts = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', $dateRange);
                if (is_array($parts) && count($parts) === 2) {
                try {
                $start = \Carbon\Carbon::parse(trim($parts[0]))->startOfDay();
                $end = \Carbon\Carbon::parse(trim($parts[1]))->endOfDay();
                if ($start && $end && $start->lte($end)) {
                $booked = \App\Models\Booking::query()
                ->where('room_type_id', $rt->id)
                // Acceptées OU déjà payées comptent comme occupées pour l'inventaire
                ->where(function($q){
                $q->where('status','accepted')
                ->orWhere('payment_status','paid');
                })
                ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function($q2) use ($start, $end) {
                $q2->where('start_date', '<=', $start->toDateString())
                    ->where('end_date', '>=', $end->toDateString());
                    });
                    })
                    ->sum('quantity');
                    $inv = is_null($rt->inventory) ? 1 : max(0, (int) $rt->inventory);
                    $avail = max(0, $inv - (int) $booked);
                    $availableQty = $avail;
                    $availabilityLabel = $avail > 0 ? ($avail . ' dispo') : 'Complet';
                    }
                    } catch (\Throwable $e) {
                    $availabilityLabel = '—';
                    }
                    }
                    }
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-900 cursor-pointer" onclick="toggleRoomTypeDetails('rt-{{ $rt->id }}')" data-rt-id="{{ $rt->id }}" data-rt-images='@json($rt->images ?? [])'>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 font-medium">{{ $rt->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="inline-flex items-center gap-1" aria-label="{{ $rt->capacity }} personne{{ $rt->capacity > 1 ? 's' : '' }}">
                                <i class="fas fa-user text-blue-600 dark:text-blue-400"></i>
                                <span>{{ $rt->capacity }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                            <span class="inline-flex items-center gap-1" aria-label="{{ $rt->beds }} lit{{ $rt->beds > 1 ? 's' : '' }}">
                                <i class="fas fa-bed text-gray-700 dark:text-gray-300"></i>
                                <span>{{ $rt->beds }}</span>
                            </span>
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            @if(!is_null($rtBasePrice))
                            {{ number_format($rtConverted, 2) }} {{ $displayCurrency }}
                            @else
                            <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            @if($availableQty !== null && $availableQty <= 0)
                                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 text-red-600 dark:text-red-400" title="Cette chambre n'est pas disponible pour les dates sélectionnées">
                                <span class="inline-flex items-center gap-2">
                                    <i class="fas fa-times-circle"></i>
                                    Indisponible à ces dates
                                </span>
                                <button type="button" class="text-blue-600 dark:text-blue-400 hover:underline sm:ml-2"
                                    onclick="(function(){
                                            var input = (window.innerWidth < 768) ? document.getElementById('ReservationDateRange2') : document.getElementById('ReservationDateRange');
                                            if (input && typeof window.openCalendarForInput === 'function') { window.openCalendarForInput(input); }
                                        })()">
                                    Changer de dates
                                </button>
    </div>
    @else
    <button type="button"
        wire:click.prevent="openSummary({{ $rt->id }})"
        class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400">
        Réserver
    </button>
    @endif
    </td>
    </tr>
    {{-- Ligne de détails repliable pour ce type de chambre --}}
    <tr id="rt-{{ $rt->id }}" class="hidden bg-gray-50 dark:bg-gray-900">
        <td colspan="5" class="px-4 py-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Description</h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300">{{ $rt->description ?? 'Aucune description fournie.' }}</p>
                    @if(is_array($rt->amenities) && count($rt->amenities))
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mt-4 mb-2">Caractéristiques de la chambre</h4>
                    <ul class="flex flex-wrap gap-2">
                        @foreach($rt->amenities as $amenity)
                        <li class="px-2 py-1 rounded bg-gray-100 dark:bg-gray-800 text-xs text-gray-700 dark:text-gray-300">{{ ucfirst($amenity) }}</li>
                        @endforeach
                    </ul>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-2">Images</h3>
                    <div class="grid grid-cols-3 gap-2">
                        @if(is_array($rt->images) && count($rt->images))
                        @foreach(array_slice($rt->images, 0, 6) as $img)
                        <img src="{{ asset('storage/' . ltrim($img, '/')) }}" alt="{{ $rt->name }}" class="w-full h-16 object-cover rounded border border-gray-200 dark:border-gray-700 cursor-pointer" onclick="openRoomTypeGallery({{ $rt->id }}, {{ $loop->index }})">
                        @endforeach
                        @else
                        <span class="text-sm text-gray-400">Aucune image</span>
                        @endif
                    </div>
                </div>
            </div>
        </td>
    </tr>
    @endforeach
    </tbody>
    </table>
</div>
<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Astuce: cliquez sur une ligne pour afficher les détails du type de chambre.</p>
<script>
    function toggleRoomTypeDetails(id) {
        const row = document.getElementById(id);
        if (!row) return;
        row.classList.toggle('hidden');
    }
</script>
</div>
@endif

{{-- Tableau fallback pour propriétés sans roomTypes (Résidence meublée simple) --}}
@if($property && (!$property->roomTypes || !$property->roomTypes->count()))
<div class="container mx-auto mt-8 px-4">
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Type</th>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Capacité</th>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Lits</th>
                    <th scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Prix</th>
                    <th id="availability" scope="col" class="px-4 py-3 text-left text-sm font-semibold text-gray-700 dark:text-gray-200">Disponibilité</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @php
                $availabilityLabel = null;
                if (!empty($dateRange)) {
                $parts = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', $dateRange);
                if (is_array($parts) && count($parts) === 2) {
                try {
                $start = \Carbon\Carbon::parse(trim($parts[0]))->startOfDay();
                $end = \Carbon\Carbon::parse(trim($parts[1]))->endOfDay();
                if ($start && $end && $start->lte($end)) {
                $existsOverlap = \App\Models\Booking::query()
                ->where('property_id', $property->id)
                ->where(function($q){
                $q->where('status','accepted')->orWhere('payment_status','paid');
                })
                ->where(function($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function($q2) use ($start, $end) {
                $q2->where('start_date', '<=', $start->toDateString())
                    ->where('end_date', '>=', $end->toDateString());
                    });
                    })
                    ->exists();
                    $availabilityLabel = $existsOverlap ? 'Non disponible aux dates choisies' : 'Disponible';
                    $isAvailable = !$existsOverlap;
                    }
                    } catch (\Throwable $e) {
                    $availabilityLabel = '—';
                    }
                    }
                    }
                    $basePrice = $property->price_per_night;
                    $user = auth()->user();
                    $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                    $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
                    $displayCurrency = ($rate && $rate > 0) ? $userCurrency : 'XOF';
                    $converted = ($rate && $rate > 0 && $basePrice !== null) ? round($basePrice * $rate, 2) : $basePrice;
                    @endphp
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200 font-medium">Logement complet</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">—</td>
                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">—</td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            @if(!is_null($basePrice))
                            {{ number_format($converted, 2) }} {{ $displayCurrency }}
                            @else
                            <span class="text-gray-400">N/A</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-200">
                            @if(isset($isAvailable) && $isAvailable === false)
                            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-1 text-red-600 dark:text-red-400">
                                <span class="inline-flex items-center gap-2"><i class="fas fa-times-circle"></i>Résidence indisponible à ces dates</span>
                                <button type="button" class="text-blue-600 dark:text-blue-400 hover:underline sm:ml-2"
                                    onclick="(function(){
                                            var input = (window.innerWidth < 768) ? document.getElementById('ReservationDateRange2') : document.getElementById('ReservationDateRange');
                                            if (input && typeof window.openCalendarForInput === 'function') { window.openCalendarForInput(input); }
                                        })()">
                                    Changer de dates
                                </button>
                            </div>
                            @elseif(!isset($isAvailable) || $isAvailable === true)
                            <button type="button" wire:click.prevent="openSummary" class="bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400">Réserver</button>
                            @else
                            <span class="text-gray-400">—</span>
                            @endif
                        </td>
                    </tr>
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Sélectionnez vos dates ci-dessus puis cliquez sur Réserver.</p>
</div>
@endif

<!-- Modal galerie pour types de chambre -->
@if(isset($showSummaryModal) && $showSummaryModal)
<div class="fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4" wire:key="summary-modal">
    <div class="relative w-full max-w-3xl">
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl overflow-hidden ring-1 ring-gray-100 dark:ring-gray-800">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/40 flex items-center justify-center text-blue-600 dark:text-blue-300">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900 dark:text-gray-50">Récapitulatif de la réservation</h3>
                </div>
                <button type="button" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-500" wire:click="closeSummary" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Body -->
            <div class="px-6 py-5 grid grid-cols-1 gap-5">
                @php
                $isHotel = $property && $property->category && in_array($property->category->name, ['Hôtel','Hotel']);
                $roomType = null;
                if ($isHotel && $selectedRoomTypeId && $property && $property->roomTypes) {
                $roomType = $property->roomTypes->firstWhere('id', (int) $selectedRoomTypeId);
                }
                @endphp
                <!-- établissement -->
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 flex items-center justify-center">
                        <i class="fas fa-building"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs uppercase tracking-wide text-gray-400">Établissement</div>
                        <div class="text-base font-semibold text-gray-900 dark:text-gray-100 break-words">{{ $property->name ?? '—' }}</div>
                    </div>
                </div>

                @if($roomType)
                <div class="flex items-start gap-3">
                    <div class="shrink-0 w-9 h-9 rounded-lg bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 flex items-center justify-center">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="min-w-0">
                        <div class="text-xs uppercase tracking-wide text-gray-400">Type de chambre</div>
                        <div class="text-base font-medium text-gray-900 dark:text-gray-100">{{ $roomType->name }}</div>
                    </div>
                </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-9 h-9 rounded-lg bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-300 flex items-center justify-center">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-gray-400">Arrivée</div>
                            <div class="text-base font-medium text-gray-900 dark:text-gray-100">
                                @php
                                try {
                                $ciFr = \Illuminate\Support\Str::title(\Carbon\Carbon::parse($checkInDate)->locale('fr')->translatedFormat('l d F Y'));
                                } catch (\Throwable $e) { $ciFr = $checkInDate; }
                                @endphp
                                {{ $ciFr }}
                            </div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-9 h-9 rounded-lg bg-rose-50 dark:bg-rose-900/40 text-rose-600 dark:text-rose-300 flex items-center justify-center">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-gray-400">Départ</div>
                            <div class="text-base font-medium text-gray-900 dark:text-gray-100">
                                @php
                                try {
                                $coFr = \Illuminate\Support\Str::title(\Carbon\Carbon::parse($checkOutDate)->locale('fr')->translatedFormat('l d F Y'));
                                } catch (\Throwable $e) { $coFr = $checkOutDate; }
                                @endphp
                                {{ $coFr }}
                            </div>
                        </div>
                    </div>

                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-9 h-9 rounded-lg bg-sky-50 dark:bg-sky-900/40 text-sky-600 dark:text-sky-300 flex items-center justify-center">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-gray-400">Quantité</div>
                            <div class="text-base font-medium text-gray-900 dark:text-gray-100">{{ max(1,(int)$quantity) }}</div>
                        </div>
                    </div>
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-9 h-9 rounded-lg bg-teal-50 dark:bg-teal-900/40 text-teal-600 dark:text-teal-300 flex items-center justify-center">
                            <i class="fas fa-tag"></i>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wide text-gray-400">Prix unitaire</div>
                            <div class="text-base font-medium text-gray-900 dark:text-gray-100">
                                {{ number_format((float)($unitPrice ?? 0), 2) }} XOF
                                <span class="text-gray-500">(≈ {{ number_format((float)($unitPriceConverted ?? 0), 2) }} {{ $convertedCurrency ?? 'XOF' }})</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-between p-4 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-3 text-gray-600 dark:text-gray-300">
                        <i class="fas fa-receipt text-lg text-blue-500"></i>
                        <span class="text-sm">Total estimé (hors taxes/conditions particulières):</span>
                    </div>
                    <div class="text-right">
                        <div class="text-xl font-extrabold text-gray-900 dark:text-gray-50">{{ number_format((float)($totalPrice ?? 0), 2) }} XOF</div>
                        <div class="text-xs text-gray-500">≈ {{ number_format((float)($convertedPrice ?? 0), 2) }} {{ $convertedCurrency ?? 'XOF' }}</div>
                    </div>
                </div>

                <div class="flex items-start gap-3 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-800 dark:text-blue-200">
                    <i class="fas fa-envelope-open-text mt-0.5"></i>
                    <p class="text-sm leading-5">
                        Après validation, vous recevrez un email récapitulatif de votre demande. Notre équipe vous contactera rapidement pour confirmer la disponibilité et finaliser la réservation.
                    </p>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/40 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-3">
                <button type="button" wire:click="closeSummary" class="px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800">
                    Annuler
                </button>
                <button type="button" wire:click="confirmReservation" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white inline-flex items-center gap-2">
                    <i class="fas fa-paper-plane"></i>
                    Soumettre la demande
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<div id="roomTypeGalleryModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-75 flex items-center justify-center">
    <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-lg w-11/12 lg:w-3/4 max-h-screen overflow-hidden">
        <button class="absolute top-2 right-2 text-gray-500 hover:text-gray-700" onclick="closeRoomTypeGallery()">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <div class="p-4">
            <div class="swiper swiper-container rtSwiper">
                <div class="swiper-wrapper" id="rt-swiper-wrapper"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </div>
</div>

<script>
    window.openRoomTypeGallery = function(roomTypeId, startIndex = 0) {
        const modal = document.getElementById('roomTypeGalleryModal');
        const wrapper = document.getElementById('rt-swiper-wrapper');
        if (!modal || !wrapper) return;
        // Trouver la ligne avec les data-attributes
        const row = document.querySelector(`tr[data-rt-id="${roomTypeId}"]`);
        if (!row) return;
        let images = [];
        try {
            const data = row.getAttribute('data-rt-images');
            images = data ? JSON.parse(data) : [];
        } catch (e) {
            images = [];
        }
        // Nettoyage
        wrapper.innerHTML = '';
        // Peupler
        (images || []).forEach((img) => {
            const src = (img || '').replace(/^\/+/, '');
            const slide = document.createElement('div');
            slide.className = 'swiper-slide flex items-center justify-center';
            slide.innerHTML = `<img src="${window.location.origin}/storage/${src}" class="max-h-[80vh] w-full max-w-full h-auto object-contain rounded-lg" alt="RoomType Image" />`;
            wrapper.appendChild(slide);
        });
        modal.classList.remove('hidden');
        setTimeout(() => {
            // Détruire instance précédente si besoin
            if (window.rtSwiper && typeof window.rtSwiper.destroy === 'function') {
                window.rtSwiper.destroy(true, true);
                window.rtSwiper = null;
            }
            window.rtSwiper = new Swiper('#roomTypeGalleryModal .rtSwiper', {
                spaceBetween: 10,
                loop: false,
                navigation: {
                    nextEl: '#roomTypeGalleryModal .swiper-button-next',
                    prevEl: '#roomTypeGalleryModal .swiper-button-prev',
                },
                pagination: {
                    el: '#roomTypeGalleryModal .swiper-pagination',
                    clickable: true,
                },
            });
            if (typeof startIndex === 'number' && startIndex >= 0) {
                try {
                    window.rtSwiper.slideTo(startIndex, 0);
                } catch (_) {}
            }
        }, 0);
    };

    window.closeRoomTypeGallery = function() {
        const modal = document.getElementById('roomTypeGalleryModal');
        if (!modal) return;
        modal.classList.add('hidden');
    };
</script>

<div id="photoGalleryModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-75 flex items-center justify-center">
    <!-- Bouton de fermeture en overlay (desktop/tablette) -->
    <button type="button" aria-label="Fermer la galerie" onclick="closeGallery()"
        class="hidden md:flex absolute top-3 right-3 z-[60] bg-black/60 hover:bg-black/70 text-white rounded-full p-3 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-black">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
    <div class="relative bg-white dark:bg-gray-900 rounded-lg shadow-lg w-11/12 lg:w-3/4 max-h-screen overflow-hidden">
        <!-- Bouton de fermeture spécifique mobile, ancré dans le conteneur blanc -->
        <button type="button" aria-label="Fermer la galerie" onclick="closeGallery()"
            class="md:hidden absolute top-2 right-2 z-10 bg-black/60 hover:bg-black/70 text-white rounded-full p-3 focus:outline-none focus:ring-2 focus:ring-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
        <div class="p-4">
            @php
            $firstImg = null;
            if ($property && $property->images && $property->images->count()) {
            $fp = $property->images[0]->image_path ?? '';
            if (\Illuminate\Support\Str::startsWith($fp, 'storage/')) { $fp = substr($fp, 8); }
            $fp = ltrim($fp, '/');
            $firstImg = asset('storage/' . $fp);
            }
            @endphp

            <!-- Galerie simple CSS/JS -->
            <div id="galleryMain" class="relative w-full flex items-center justify-center">
                <button type="button" onclick="window.galleryPrev()" class="hidden md:flex absolute left-2 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white text-gray-800 rounded-full p-2 shadow">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <img id="galleryMainImage" src="{{ $firstImg }}" alt="{{ $property->name ?? 'Image' }}" class="max-h-[80vh] w-full max-w-full h-auto object-contain rounded-lg" />
                <button type="button" onclick="window.galleryNext()" class="hidden md:flex absolute right-2 top-1/2 -translate-y-1/2 bg-white/70 hover:bg-white text-gray-800 rounded-full p-2 shadow">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>

            <!-- Miniatures (desktop uniquement) -->
            <div id="galleryThumbs" class="mt-4 hidden md:flex gap-2 overflow-x-auto">
                @foreach($property->images as $idx => $image)
                @php
                $imgPath = $image->image_path ?? '';
                if (\Illuminate\Support\Str::startsWith($imgPath, 'storage/')) { $imgPath = substr($imgPath, 8); }
                $imgPath = ltrim($imgPath, '/');
                $url = asset('storage/' . $imgPath);
                @endphp
                <img src="{{ $url }}" data-src="{{ $url }}" data-index="{{ $idx }}" alt="Miniature {{ $idx + 1 }}" class="thumb w-20 h-20 object-cover rounded-lg border-2 border-transparent hover:border-blue-500 cursor-pointer" />
                @endforeach
            </div>

            <style>
                #photoGalleryModal #galleryThumbs .thumb.selected {
                    border-color: #3b82f6;
                    box-shadow: 0 0 0 2px rgba(59, 130, 246, .5);
                }

                #photoGalleryModal #galleryMain button {
                    transition: background-color .15s ease;
                }
            </style>

            <!-- script pour la galerie (CSS/JS pur, sans Swiper) -->
            <script>
                (function() {
                    let images = [];
                    let current = 0;
                    let touchStartX = 0,
                        touchStartY = 0,
                        touchMoved = false;

                    function collectImages() {
                        const thumbs = document.querySelectorAll('#photoGalleryModal #galleryThumbs img.thumb');
                        images = Array.from(thumbs).map(img => img.getAttribute('data-src') || img.src);
                    }

                    function updateMain() {
                        const main = document.getElementById('galleryMainImage');
                        if (!main || !images.length) return;
                        main.src = images[current];
                        document.querySelectorAll('#photoGalleryModal #galleryThumbs img.thumb').forEach((el, idx) => {
                            el.classList.toggle('selected', idx === current);
                        });
                    }

                    function setIndex(i) {
                        if (!images.length) collectImages();
                        if (!images.length) return;
                        const n = images.length;
                        current = ((i % n) + n) % n;
                        updateMain();
                    }

                    window.galleryNext = function() {
                        setIndex(current + 1);
                    };
                    window.galleryPrev = function() {
                        setIndex(current - 1);
                    };

                    window.openGallery = function(index = 0) {
                        const modal = document.getElementById('photoGalleryModal');
                        if (!modal) return;
                        modal.classList.remove('hidden');
                        collectImages();
                        setIndex(Number.isFinite(index) ? index : 0);
                    };
                    window.closeGallery = function() {
                        const modal = document.getElementById('photoGalleryModal');
                        if (!modal) return;
                        modal.classList.add('hidden');
                    };

                    // Gestes tactiles (swipe gauche/droite) pour mobile
                    (function bindTouchGestures() {
                        const area = document.getElementById('galleryMain');
                        if (!area || area.dataset.touchBound === '1') return;
                        area.dataset.touchBound = '1';
                        const THRESHOLD = 40; // px
                        area.addEventListener('touchstart', (e) => {
                            if (!e.touches || !e.touches.length) return;
                            const t = e.touches[0];
                            touchStartX = t.clientX;
                            touchStartY = t.clientY;
                            touchMoved = false;
                        }, {
                            passive: true
                        });
                        area.addEventListener('touchmove', (e) => {
                            if (!e.touches || !e.touches.length) return;
                            const t = e.touches[0];
                            const dx = t.clientX - touchStartX;
                            const dy = t.clientY - touchStartY;
                            if (Math.abs(dx) > 10 && Math.abs(dx) > Math.abs(dy)) {
                                // Empêcher le scroll vertical pendant un swipe horizontal
                                e.preventDefault();
                                touchMoved = true;
                            }
                        }, {
                            passive: false
                        });
                        area.addEventListener('touchend', (e) => {
                            if (!e.changedTouches || !e.changedTouches.length) return;
                            const t = e.changedTouches[0];
                            const dx = t.clientX - touchStartX;
                            if (Math.abs(dx) >= THRESHOLD) {
                                if (dx < 0) {
                                    window.galleryNext();
                                } else {
                                    window.galleryPrev();
                                }
                            }
                        }, {
                            passive: true
                        });
                    })();

                    // Clic sur miniature
                    document.addEventListener('click', function(e) {
                        const t = e.target.closest('#photoGalleryModal #galleryThumbs img.thumb');
                        if (!t) return;
                        const idx = parseInt(t.getAttribute('data-index') || '0', 10);
                        setIndex(idx);
                    });

                    // Navigation clavier dans la modale
                    document.addEventListener('keydown', function(e) {
                        const modal = document.getElementById('photoGalleryModal');
                        if (!modal || modal.classList.contains('hidden')) return;
                        if (e.key === 'ArrowRight') {
                            e.preventDefault();
                            window.galleryNext();
                        } else if (e.key === 'ArrowLeft') {
                            e.preventDefault();
                            window.galleryPrev();
                        } else if (e.key === 'Escape') {
                            e.preventDefault();
                            window.closeGallery();
                        }
                    });
                })();
            </script>
        </div>
    </div>
</div>

<!-- Reviews section -->
<div class="container bg-transparent dark:bg-transparent mx-auto mt-8 mb-8 transition-colors duration-300">
    <div id="reviews" class="mt-8">
        <div class="mb-6 px-4 sm:px-0">
            <span class="block text-xl font-semibold text-gray-800 dark:text-gray-100 mt-6 mb-4 sm:mt-0 sm:mb-6">Ce que les personnes ayant séjourné ici ont adoré :</span>
        </div>

        @if(!$reviews || $reviews->isEmpty())
        <div class="flex justify-center">
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-6 w-full max-w-lg shadow">
                <p class="text-xl italic font-medium text-gray-700 dark:text-gray-200 text-center">Aucun avis pour cette propriété pour le moment.</p>
            </div>
        </div>
        @else
        <!-- Carrousel mobile (Swiper) -->
        <div class="block md:hidden">
            <div id="reviewsCarousel" class="swiper-container reviews-swiper">
                <div class="swiper-wrapper">
                    @foreach($reviews as $review)
                    <div class="swiper-slide">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 flex flex-col gap-3 border border-gray-100 dark:border-gray-700">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-300">
                                    <span>
                                        @if(isset($review->user->firstname) && $review->user->firstname)
                                        {{ strtoupper(mb_substr($review->user->firstname, 0, 1)) }}
                                        @elseif(isset($review->user->name) && $review->user->name)
                                        {{ strtoupper(mb_substr($review->user->name, 0, 1)) }}
                                        @else
                                        <i class="fas fa-user"></i>
                                        @endif
                                    </span>
                                </div>
                                <div>
                                    <div class="flex items-center gap-1">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-600 dark:text-gray-400' }}" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" />
                                            </svg>
                                            @endfor
                                    </div>
                                    <div class="text-sm text-gray-700 dark:text-gray-300 font-semibold">
                                        {{ (isset($review->user->firstname) && $review->user->firstname) ? $review->user->firstname : ($review->user->name ?? 'Utilisateur inconnu') }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-400">Posté le {{ $review->created_at->format('d/m/Y') }}</div>
                                </div>
                            </div>
                            <div class="flex-1">
                                <p class="text-gray-800 dark:text-gray-100 text-base leading-relaxed">{{ $review->review }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="swiper-pagination"></div>
            </div>
        </div>

        <!-- Grille desktop/tablette -->
        <div class="hidden md:grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($reviews as $review)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 flex flex-col gap-3 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900 flex items-center justify-center text-2xl font-bold text-blue-600 dark:text-blue-300">
                        <span>
                            @if(isset($review->user->firstname) && $review->user->firstname)
                            {{ strtoupper(mb_substr($review->user->firstname, 0, 1)) }}
                            @elseif(isset($review->user->name) && $review->user->name)
                            {{ strtoupper(mb_substr($review->user->name, 0, 1)) }}
                            @else
                            <i class="fas fa-user"></i>
                            @endif
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-600 dark:text-gray-400' }}" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" />
                                </svg>
                                @endfor
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 font-semibold">
                            {{ (isset($review->user->firstname) && $review->user->firstname) ? $review->user->firstname : ($review->user->name ?? 'Utilisateur inconnu') }}
                        </div>
                        <div class="text-xs text-gray-400 dark:text-gray-400">Posté le {{ $review->created_at->format('d/m/Y') }}</div>
                    </div>
                </div>
                <div class="flex-1">
                    <p class="text-gray-800 dark:text-gray-100 text-base leading-relaxed">{{ $review->review }}</p>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>


    <!-- script pour le partage (conservé) -->
    <script>
        // Fonction d'ouverture du modal de partage
        function shareProperty() {
            document.getElementById('shareModal').classList.remove('hidden');
        }

        function closeShareModal() {
            document.getElementById('shareModal').classList.add('hidden');
        }

        function copyShareLink() {
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(function() {
                if (window.Swal) {
                    Swal.fire('Lien copié !', 'Le lien de la page a été copié dans le presse-papier.', 'success');
                } else {
                    alert('Lien copié dans le presse-papier !');
                }
            }, function() {
                if (window.Swal) {
                    Swal.fire('Erreur', 'Impossible de copier le lien.', 'error');
                } else {
                    alert('Impossible de copier le lien.');
                }
            });
        }

        // Redirection directe vers le chat après soumission du formulaire
        document.addEventListener('DOMContentLoaded', function() {
            const bookingForms = document.querySelectorAll('form[wire\\:submit\\.prevent="addBooking"]');
            bookingForms.forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    // Laisser Livewire gérer la soumission, puis rediriger côté serveur
                    // On ne fait rien ici, la redirection doit être gérée côté Livewire PHP
                });
            });
        });
    </script>

    <!-- script pour le carrousel des avis (responsive) -->
    <script>
        (function() {
            const BREAKPOINT = 768; // md
            function initReviewsSwiper() {
                const container = document.querySelector('#reviewsCarousel');
                if (!container || typeof Swiper === 'undefined') return;

                // Détruire une éventuelle instance existante
                if (window.swiperReviews && typeof window.swiperReviews.destroy === 'function') {
                    window.swiperReviews.destroy(true, true);
                    window.swiperReviews = null;
                }

                window.swiperReviews = new Swiper('#reviewsCarousel', {
                    slidesPerView: 1.05,
                    spaceBetween: 12,
                    autoHeight: true,
                    pagination: {
                        el: '#reviewsCarousel .swiper-pagination',
                        clickable: true,
                    },
                });
            }

            function destroyReviewsSwiper() {
                if (window.swiperReviews && typeof window.swiperReviews.destroy === 'function') {
                    window.swiperReviews.destroy(true, true);
                    window.swiperReviews = null;
                }
            }

            function updateReviewsSwiper() {
                const isMobile = window.innerWidth < BREAKPOINT;
                if (isMobile) {
                    initReviewsSwiper();
                } else {
                    destroyReviewsSwiper();
                }
            }

            const debounce = (fn, delay = 150) => {
                let t;
                return () => {
                    clearTimeout(t);
                    t = setTimeout(fn, delay);
                };
            };

            document.addEventListener('DOMContentLoaded', updateReviewsSwiper);
            window.addEventListener('resize', debounce(updateReviewsSwiper));
            window.addEventListener('livewire:navigated', () => setTimeout(updateReviewsSwiper, 0));
        })();
    </script>
    <!-- script pour le modal de la galerie (wrappers) -->
    <script>
        // Les fonctions openGallery/closeGallery/galleryNext/galleryPrev sont définies plus haut
        // et gèrent une galerie CSS/JS sans Swiper.
    </script>
</div>