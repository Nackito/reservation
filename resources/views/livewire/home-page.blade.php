<div>
    <style>
        .autocomplete-dropdown {
            animation: fadeInDown 0.2s ease-out;
        }
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .autocomplete-item:hover {
            background-color: #eff6ff;
            color: #1d4ed8;
        }
    </style>
    
    <!-- Section de bienvenue -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white">
        <div class="container mx-auto py-16 px-4">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-4">Bienvenue sur notre site</h1>
                <p class="text-xl text-blue-100">Trouvez la propriété idéale pour votre séjour en Côte d'Ivoire</p>
            </div>
            
            <!-- Barre de recherche -->
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
                <form wire:submit.prevent="search" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-1"></i>Ville
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="city"
                                wire:model.live="searchCity"
                                placeholder="Entrez une ville de Côte d'Ivoire..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                                autocomplete="off">
                            
                            <!-- Suggestions d'autocomplétion pour les villes -->
                            @if($showCitySuggestions && count($citySuggestions) > 0)
                            <div class="autocomplete-dropdown absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach($citySuggestions as $suggestion)
                                <div wire:click="selectCity('{{ $suggestion }}')"
                                     class="autocomplete-item px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-center transition duration-150">
                                    <i class="fas fa-map-marker-alt text-blue-500 mr-3"></i>
                                    <span class="text-gray-900">{{ $suggestion }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <div>
                        <label for="municipality" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-building mr-1"></i>Quartier
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                id="municipality"
                                wire:model.live="searchMunicipality"
                                placeholder="Entrez un quartier..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                                autocomplete="off">
                            
                            <!-- Suggestions d'autocomplétion pour les quartiers -->
                            @if($showMunicipalitySuggestions && count($municipalitySuggestions) > 0)
                            <div class="autocomplete-dropdown absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                @foreach($municipalitySuggestions as $suggestion)
                                <div wire:click="selectMunicipality('{{ $suggestion }}')"
                                     class="autocomplete-item px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-center transition duration-150">
                                    <i class="fas fa-building text-blue-500 mr-3"></i>
                                    <span class="text-gray-900">{{ $suggestion }}</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                            <i class="fas fa-search mr-2"></i>
                            Rechercher
                        </button>
                        
                        @if($showResults)
                        <button
                            type="button"
                            wire:click="clearSearch"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-200"
                            title="Effacer la recherche">
                            <i class="fas fa-times"></i>
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Résultats de recherche ou propriétés -->
    <div class="container mx-auto mt-8 px-4">
        @if($showResults)
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800 mb-2">
                    Résultats de recherche
                    @if($searchCity || $searchMunicipality)
                        pour
                        @if($searchCity)
                            <span class="text-blue-600">"{{ $searchCity }}"</span>
                        @endif
                        @if($searchCity && $searchMunicipality)
                            ,
                        @endif
                        @if($searchMunicipality)
                            <span class="text-blue-600">"{{ $searchMunicipality }}"</span>
                        @endif
                    @endif
                </h2>
                <p class="text-gray-600 text-lg">{{ count($properties) }} propriété(s) trouvée(s)</p>
            </div>
            
            @if(count($properties) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($properties as $property)
                    <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition duration-200">
                        @if($property->firstImage())
                        <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}" alt="{{ $property->name }}" class="w-full h-48 object-cover">
                        @else
                        <img src="{{ asset('images/default-image.jpg') }}" alt="Propriété par défaut" class="w-full h-48 object-cover">
                        @endif
                        <div class="p-4">
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">{{ $property->name ?? 'Nom non disponible' }}</h3>
                            <p class="text-gray-600 mb-2">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ $property->city ?? 'Ville non disponible' }}
                                @if($property->municipality)
                                    , {{ $property->municipality }}
                                @endif
                            </p>
                            <p class="text-gray-500 mb-4">{{ Str::words($property->description ?? 'Description non disponible', 15, '...') }}</p>
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-blue-600">{{ $property->price_per_night ?? 'Prix non disponible' }} €/nuit</span>
                                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                   class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200">
                                    Réserver
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-12">
                    <div class="text-gray-400 mb-4">
                        <i class="fas fa-search text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucune propriété trouvée</h3>
                    <p class="text-gray-500 mb-4">Essayez de modifier vos critères de recherche ou explorez toutes nos propriétés</p>
                    <button
                        wire:click="clearSearch"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200">
                        Voir toutes les propriétés
                    </button>
                </div>
            @endif
        @else
            <h2 class="text-3xl font-bold mb-6 text-gray-800">Nos propriétés populaires</h2>

        <!-- Swiper Container -->
        <div class="swiper-container max-w-full mx-auto relative"> <!-- Limite la largeur et empêche le débordement -->
            <div class="swiper-wrapper">
                @foreach($properties as $property)
                <div class="swiper-slide">
                    <div class="bg-white shadow-md rounded-lg overflow-hidden w-full h-full">
                        @if($property->firstImage())
                        <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}" alt="{{ $property->name }}" class="w-full h-auto object-cover">
                        @else
                        <img src="{{ asset('images/default-image.jpg') }}" alt="Propriété par défaut" class="w-full object-cover">
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
        @endif
    </div>
</div>