<div>
    <div class="bg-gray-100">
        <div class="container mx-auto py-8">
            <h1 class="text-2xl font-bold mb-4">Bienvenue sur notre site</h1>
            <p class="text-gray-600">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Quos autem, eaque, voluptas, officia culpa ipsam laudantium quae quia nemo alias quidem doloribus.</p>
        </div>
    </div>
    <div class="container mx-auto mt-8">
        <h1 class="text-2xl font-bold mb-4">Quelques propriétés</h1>

        <!-- Swiper Container -->
        <div class="swiper-container max-w-full mx-auto relative"> <!-- Limite la largeur et empêche le débordement -->
            <div class="swiper-wrapper">
                @foreach($properties as $property)
                <div class="swiper-slide">
                    <div class="bg-white shadow-md rounded-lg overflow-hidden w-full h-full">
                        @if($property->firstImage())
                        <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}" alt="{{ $property->name }}" class="w-full h-auto object-cover">
                        @else
                        <img src="{{ asset('images/default-image.jpg') }}" alt="Image par défaut" class="w-full object-cover">
                        @endif
                        <div class="p-4">
                            <h3 class="text-lg text-gray-800">{{ $property->name ?? 'Nom non disponible' }}</h3>
                            <p class="text-gray-700">{{ $property->city ?? 'Ville non disponible' }}, {{ Str::words($property->district ?? 'Quartier non disponible') }}</p>
                            <p class="text-gray-500 mt-5">{{ Str::words($property->description ?? 'Description non disponible', 20, '...') }}</p>
                            <p class="text-gray-600 text-right font-bold mt-5">{{ $property->price_per_night ?? 'Prix non disponible' }} € par nuit</p>
                            <div class="mt-4">
                                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}" class="border border-blue-500 bg-white-500 text-blue-500 text-center py-2 px-4 rounded block w-full">Réserver cette résidence</a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Add Navigation -->
            <div class="swiper-button-next absolute right-0 top-1/2 transform -translate-y-1/2 z-10"> <!-- Bouton à l'intérieur du conteneur -->
                <ion-icon class="arrow" name="caret-forward-outline"></ion-icon>
            </div>
            <div class="swiper-button-prev absolute left-0 top-1/2 transform -translate-y-1/2 z-10"> <!-- Bouton à l'intérieur du conteneur -->
                <ion-icon class="arrow" name="caret-back-outline"></ion-icon>
            </div>
        </div>
    </div>
</div>