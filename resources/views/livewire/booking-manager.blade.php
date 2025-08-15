<div>
    <!-- Début du composant Livewire : tout est enveloppé dans ce div racine -->
    <div class="container mx-auto py-8">

        <form wire:submit.prevent="addBooking" class="mb-4">
            <div class="flex mt-4 flex-col sm:flex-row gap-2 sm:gap-3 items-center bg-white rounded-lg p-2 dark:bg-gray-800">
                <div class="w-full">
                    <p class="py-3 px-4 block w-full border-transparent rounded-lg text-sm
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent
                    dark:text-gray-400 dark:focus:ring-gray-600" readonly> {{ $property->name ?? '' }} </p>
                </div>

                <div class="w-full">
                    <input type="date" wire:model="checkInDate" id="ReservationCheckInBottom" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent
                    dark:text-gray-400 dark:focus:ring-gray-600" min="{{ now()->format('Y-m-d') }}">
                    @error('checkInDate') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>
                <div class="w-full">
                    <input type="date" wire:model="checkOutDate" id="ReservationCheckOutBottom" wire:change="calculateTotalPrice" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent
                    dark:text-gray-400 dark:focus:ring-gray-600" min="{{ $checkInDate }}">
                    @error('checkOutDate') <span class="text-red-500">{{ $message }}</span> @enderror
                </div>
                @if(Auth::check())
                <button type="submit" wire:submit.prevent="addBooking" id="confirm-booking" class="bg-blue-500 text-white py-2 px-4 rounded">
                    Confirmer
                </button>
                @else
                <button type="button" onclick="showReservationLoginNotification()" class="bg-blue-500 text-white py-2 px-4 rounded">
                    Confirmer
                </button>
                @endif
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

    <div class="container bg-white mx-auto mt-8">
        <!-- Overview section -->
        <div id="overview" class="bg-white shadow-md rounded-lg overflow-hidden w-61 h-90">
            <div class="flex justify-between items-start pl-4 pt-6 pr-4">
                <div>
                    <h2 class="text-2xl lg:text-3xl text-gray-800 font-inter font-extrabold">{{ $property->name ?? 'Nom non disponible' }}</h2>
                    <p class="text-lg lg:text-xl text-gray-700">
                        <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>
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
                    <!-- Bouton de contact employé (connexion requise) -->
                    @if($property)
                    @if(Auth::check())
                    <button onclick="openContactModal()" class="flex items-center justify-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg shadow transition" title="Contacter un employé de la plateforme">
                        <i class="fas fa-headset text-lg"></i>
                        <span class="hidden sm:inline ml-1">Contacter un employé</span>
                    </button>
                    @else
                    <button onclick="showContactLoginNotification()" class="flex items-center justify-center px-3 py-2 bg-green-100 hover:bg-green-200 text-green-700 rounded-lg shadow transition" title="Connectez-vous pour contacter un employé">
                        <i class="fas fa-headset text-lg"></i>
                        <span class="hidden sm:inline ml-1">Contacter un employé</span>
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
                            <h2 class="text-2xl font-bold mb-4 text-gray-800">Contacter un employé</h2>
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
                        <img src="{{ asset('storage/' . $image->image_path) }}" alt="Image de la propriété" class="w-full h-auto object-cover rounded-lg cursor-pointer" onclick="openGallery({{ $index }})">
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
            <div id="house-rules" class="bg-white shadow-md rounded-lg p-4 ">
                <h2 class="text-2xl font-bold text-gray-800 dark:text-dark">Les équipements de l'établissement</h2>
                <ul class="list-none mt-4 text-gray-600 dark:text-gray-400">
                    @forelse($property->features as $feature)
                    <li class="flex items-center mb-2">
                        @php
                        $iconClass = $featureIcons[$feature] ?? 'fa-circle'; // Icône par défaut si aucune correspondance
                        @endphp
                        <i class="fas {{ $iconClass }} text-blue-500 mr-2"></i>
                        <span>{{ $feature }}</span>
                    </li>
                    @empty
                    <li>Aucun équipement disponible pour cet établissement.</li>
                    @endforelse
                </ul>
                <div class="p-4">
                    <p class="text-gray-600 text-right font-bold mt-5">{{ $property->price_per_night ?? 'Prix non disponible' }} FrCFA par nuit</p>
                    <div class="mt-4">
                        <a href="#Reservation" class="border border-blue-500 bg-white-500 text-blue-500 text-center py-2 px-4 rounded block w-full">Réserver cette résidence</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="photoGalleryModal" class="fixed inset-0 z-50 hidden bg-black bg-opacity-75 flex items-center justify-center">
        <div class="relative bg-white rounded-lg shadow-lg w-11/12 lg:w-3/4 max-h-screen overflow-y-auto">
            <button class="absolute top-2 right-2 text-gray-500 hover:text-gray-700" onclick="closeGallery()">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div class="p-4 flex flex-col items-center">
                <div id="galleryMainImageContainer" class="mb-4">
                    <img id="galleryMainImage" src="{{ asset('storage/' . ($property->images[0]->image_path ?? '')) }}" alt="Image principale" class="w-full max-h-96 object-contain rounded-lg">
                </div>
                <div class="grid grid-cols-3 sm:grid-cols-4 lg:grid-cols-6 gap-2">
                    @foreach($property->images as $idx => $image)
                    <img src="{{ asset('storage/' . $image->image_path) }}"
                        alt="{{ $property->name ?? 'Propriété' }} chambre ou espace principal avec décoration moderne et ambiance accueillante dans un environnement résidentiel lumineux et confortable"
                        class="w-20 h-20 object-cover rounded-lg cursor-pointer border-2 border-transparent hover:border-blue-500"
                        onclick="setGalleryImage({{ $idx }})">
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="p-4">
        <p class="text-gray-500 mt-5">
            {!! $property->description ?? 'Description non disponible' !!}
        </p>
        <p id="pricing" class="text-gray-600  mt-5">Vous pouvez disposez de ce logement à <span class="text-xl font-bold"> {{ $property->price_per_night }} FrCFA par nuit</span></p>
    </div>
</div>

<!-- Info section -->
<div class="container bg-white mx-auto mt-8">
    <div id="info" class="text-gray-500 mt-5 bg-white shadow-md rounded-lg p-4">
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

<!-- Reservation form -->
<div class="container mx-auto mt-8">
    <h1 class="block text-3xl font-bold text-gray-800 sm:text-4xl lg:text-2xl lg:leading-tight dark:text-black mt-6 mb-4 sm:mt-0 sm:mb-6 px-4 sm:px-0">Entrez vos dates</h1>

    <form wire:submit.prevent="addBooking" class="mb-4">
        <div class="flex mt-4 flex-col sm:flex-row gap-2 sm:gap-3 items-center bg-white rounded-lg p-2 dark:bg-gray-800">
            <div class="w-full">
                <p class="py-3 px-4 block w-full border-transparent rounded-lg text-sm
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent
                    dark:text-gray-400 dark:focus:ring-gray-600" readonly> {{ $property->name ?? '' }} </p>
            </div>

            <div class="w-full" id="Reservation">
                <input type="date" id="ReservationCheckIn" wire:model="checkInDate" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm cursor-pointer
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent
                    dark:text-gray-400 dark:focus:ring-gray-600" min="{{ now()->format('Y-m-d') }}">
                @error('checkInDate') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <div class="w-full">
                <input type="date" id="ReservationCheckOut" wire:model="checkOutDate" wire:change="calculateTotalPrice" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm cursor-pointer
                    focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 
                    disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent 
                    dark:text-gray-400 dark:focus:ring-gray-600" min="{{ $checkInDate }}">
                @error('checkOutDate') <span class="text-red-500">{{ $message }}</span> @enderror
            </div>
            <script>
                // Force l'ouverture du calendrier natif sur tout clic dans le champ pour les deux formulaires
                document.addEventListener('DOMContentLoaded', function() {
                    const ids = [
                        'ReservationCheckIn',
                        'ReservationCheckOut',
                        'ReservationCheckInBottom',
                        'ReservationCheckOutBottom'
                    ];
                    ids.forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el) {
                            el.addEventListener('click', function(e) {
                                this.showPicker && this.showPicker();
                            });
                        }
                    });
                });
            </script>
            @if(Auth::check())
            <button type="submit" wire:submit.prevent="addBooking" id="confirm-booking" class="bg-blue-500 text-white py-2 px-4 rounded">
                Confirmer
            </button>
            @else
            <button type="button" onclick="showReservationLoginNotification()" class="bg-blue-500 text-white py-2 px-4 rounded">
                Confirmer
            </button>
            @endif
        </div>
    </form>
</div>

<!-- Reviews section -->
<div class="container bg-white mx-auto mt-8 mb-8">
    <div id="reviews" class="mt-8">
        <div class="mb-6 px-4 sm:px-0">
            <span class="block text-xl font-semibold text-gray-800 mt-6 mb-4 sm:mt-0 sm:mb-6">Ce que les personnes ayant séjourné ici ont adoré :</span>
        </div>

        @if(!$reviews || $reviews->isEmpty())
        <div class="flex justify-center">
            <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-6 w-full max-w-lg shadow">
                <p class="text-xl italic font-medium text-gray-700 dark:text-white text-center">Aucun avis pour cette propriété pour le moment.</p>
            </div>
        </div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($reviews as $review)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 flex flex-col gap-3 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center text-2xl font-bold text-blue-600">
                        <span>
                            @if(isset($review->user->name))
                            {{ strtoupper(mb_substr($review->user->name, 0, 1)) }}
                            @else
                            <i class="fas fa-user"></i>
                            @endif
                        </span>
                    </div>
                    <div>
                        <div class="flex items-center gap-1">
                            @for($i = 1; $i <= 5; $i++)
                                <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-400' : 'text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" />
                                </svg>
                                @endfor
                        </div>
                        <div class="text-sm text-gray-700 dark:text-gray-300 font-semibold">
                            {{ $review->user->name ?? 'Utilisateur inconnu' }}
                        </div>
                        <div class="text-xs text-gray-400">Posté le {{ $review->created_at->format('d/m/Y') }}</div>
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
                    <p class="text-gray-700 dark:text-white">Vous allez payer <span id="totalPrice"></span> FrCFA pour cette réservation. Voulez-vous confirmer ?</p>
                </div>
                <div class="flex justify-end pt-4">
                    <button class="bg-gray-500 text-white px-4 py-2 rounded mr-2" onclick="closeModal()">Annuler</button>
                    <button class="bg-blue-500 text-white px-4 py-2 rounded" onclick="confirmBooking()">Confirmer</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation -->
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
    </script>
    <!-- script pour le swiper -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation du Swiper des miniatures
            const swiperThumbs = new Swiper('.mySwiper2', {
                spaceBetween: 10,
                slidesPerView: 4,
                freeMode: true,
                watchSlidesProgress: true, // Permet de suivre la progression des miniatures
            });

            // Initialisation du Swiper principal
            const swiperMain = new Swiper('.mySwiper', {
                spaceBetween: 10,
                navigation: {
                    nextEl: '.swiper-button-next',
                    prevEl: '.swiper-button-prev',
                },
                thumbs: {
                    swiper: swiperThumbs, // Connecte le Swiper principal aux miniatures
                },
            });
        });
    </script>
    <!-- script pour le modal de la galerie -->
    @php
    $galleryImages = collect($property->images)->map(function($img) {
    return asset('storage/' . $img->image_path);
    });
    @endphp
    <script>
        // Injection du tableau d'images pour la galerie (compatible tous éditeurs)
        let galleryImages = @json($galleryImages);

        function openGallery(index = 0) {
            document.getElementById('photoGalleryModal').classList.remove('hidden');
            setGalleryImage(index);
        }

        function closeGallery() {
            document.getElementById('photoGalleryModal').classList.add('hidden');
        }

        function setGalleryImage(idx) {
            const mainImg = document.getElementById('galleryMainImage');
            if (mainImg && galleryImages[idx]) {
                mainImg.src = galleryImages[idx];
            }
        }
    </script>
</div>