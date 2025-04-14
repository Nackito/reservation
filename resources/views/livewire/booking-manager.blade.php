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
                    <input type="date" id="Reservation" wire:model="checkInDate" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm 
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
    <div class="relative">
        <nav id="menu" class="hidden lg:flex flex-col lg:flex-row justify-center space-x-4 bg-white dark:bg-gray-800 p-4 rounded-lg shadow-md">
            <a href="#overview" class="nav-link text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Vue d'ensemble</a>
            <a href="#pricing" class="nav-link text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Tarifs</a>
            <a href="#info" class="nav-link text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">À savoir</a>
            <a href="#reviews" class="nav-link text-gray-700 dark:text-gray-300 hover:text-blue-500 dark:hover:text-blue-400 font-semibold">Avis des clients</a>
        </nav>
    </div>

    <div class="container bg-white mx-auto mt-8">
        <!-- Overview section -->
        <div id="overview" class="bg-white shadow-md rounded-lg overflow-hidden w-61 h-90">
            <div class="pl-4 pt-6">
                <h2 class="text-2xl lg:text-3xl text-gray-800 font-inter font-extrabold">{{ $property->name ?? 'Nom non disponible' }}</h2>
                <p class="text-lg lg:text-xl text-gray-700">
                    <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>
                    {{ $property->city ?? 'Ville non disponible' }}, {{ $property->municipality }}, {{ $property->district ?? 'Quartier non disponible' }}
                </p>
            </div>

            <div class="container mx-auto mt-8 grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Section des images (2/3) -->
                <div id="PropertyImage" class="lg:col-span-2 pl-4 pr-4">
                    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        @foreach($property->images as $index => $image)
                        @if ($index < 3)
                            <div class="image-container {{ $index % 3 === 0 ? 'large' : 'small' }}">
                            <img src="{{ asset('storage/' . $image->image_path) }}" alt="Image de la propriété" class="w-full h-auto object-cover rounded-lg">
                            @if($index === 2 && $property->images->count() > 3)
                            <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center text-white text-lg font-bold cursor-pointer" onclick="openGallery()">
                                +{{ $property->images->count() - 3 }}
                            </div>
                            @endif
                    </div>
                    @endif
                    @endforeach
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
                        <p class="text-gray-600 text-right font-bold mt-5">{{ $property->price_per_night ?? 'Prix non disponible' }} € par nuit</p>
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
                <div class="p-4 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach($property->images as $image)
                    <div class="image-container">
                        <img src="{{ asset('storage/' . $image->image_path) }}" alt="Image de la propriété" class="w-full h-auto object-cover rounded-lg">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="p-4">
            <p class="text-gray-500 mt-5">
                {!! $property->description ?? 'Description non disponible' !!}
            </p>
            <p id="pricing" class="text-gray-600  mt-5">Vous pouvez disposez de ce logement à <span class="text-xl font-bold"> {{ $property->price_per_night }} euros par nuit</span></p>
        </div>
    </div>

    <!-- Info section -->
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

    <!-- Reviews section -->
    <div id="reviews" class="mt-8">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Avis des clients</h2>

        @if($reviews->isEmpty())
        <p class="text-gray-600 dark:text-gray-400">Aucun avis pour cette propriété pour le moment.</p>
        @else
        @foreach($reviews as $review)
        <div class="bg-white shadow-md rounded-lg p-4 mb-4">
            <div class="flex items-center mb-2">
                <div class="flex items-center">
                    @for($i = 1; $i <= 5; $i++)
                        <svg class="w-5 h-5 {{ $i <= $review->rating ? 'text-yellow-500' : 'text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 .587l3.668 7.568 8.332 1.151-6.064 5.868 1.464 8.826L12 18.896l-7.4 4.104 1.464-8.826L0 9.306l8.332-1.151z" />
                        </svg>
                        @endfor
                </div>
                <p class="ml-2 text-sm text-gray-500">{{ $review->user->name ?? 'Utilisateur inconnu' }}</p>
            </div>
            <blockquote class="text-xl italic font-semibold text-gray-900 dark:text-dark">
                <svg class="w-8 h-8 text-gray-400 dark:text-gray-600 mb-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 18 14">
                    <path d="M6 0H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3H2a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h4v1a3 3 0 0 1-3 3h-1a1 1 0 0 0 0 2h1a5.006 5.006 0 0 0 5-5V2a2 2 0 0 0-2-2Z" />
                </svg>
                <p>"{{ $review->review }}"</p>
            </blockquote>
            <p class="text-sm text-gray-500 mt-2">Posté le : {{ $review->created_at->format('d/m/Y') }}</p>
        </div>
        @endforeach
        @endif
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
<script>
    function openGallery() {
        document.getElementById('photoGalleryModal').classList.remove('hidden');
    }

    function closeGallery() {
        document.getElementById('photoGalleryModal').classList.add('hidden');
    }
</script>

</div>