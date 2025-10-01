{{--
    Vue Livewire pour la page d'accueil avec recherche de propriétés

    Fonctionnalités :
    - Barre de recherche avec autocomplétion pour les villes de Côte d'Ivoire
    - Recherche par quartier/municipality depuis la base de données
    - Affichage des résultats de recherche ou des propriétés populaires
    - Interface responsive avec Tailwind CSS
    - Intégration Swiper.js pour le carrousel de propriétés

    Dépendances :
    - Livewire (composant réactif)
    - Tailwind CSS (styles)
    - Font Awesome (icônes)
    - Swiper.js (carrousel)
    - CSS personnalisé : /css/autocomplete.css
--}}

<div>
    {{-- Section de bienvenue avec image d'arrière-plan --}}
    <div class="welcome-section relative bg-cover bg-center bg-no-repeat text-white dark:text-white" style="--welcome-bg: url('{{ asset('images/welcome-bg.jpg') }}')">
        <div class="container mx-auto py-16 px-4">
            {{-- Titre et sous-titre de bienvenue --}}
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-4">Bienvenue sur Afridayz</h1>
                <p class="text-xl text-blue-100">Trouvez la propriété idéale pour votre séjour en Côte d'Ivoire</p>
            </div>

            {{--
                Formulaire de recherche avec autocomplétion et recherche automatique
                - Recherche par ville avec suggestions prédéfinies + BDD
                - Recherche par quartier/municipality depuis la BDD
                - Recherche automatique en temps réel
            --}}
            <div class="max-w-4xl mx-auto bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6 transition-colors duration-300">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    {{-- Champ de recherche universel (ville, commune ou quartier) --}}
                    <div class="col-span-1 md:col-span-2 lg:col-span-3">
                        <label for="searchQuery" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                            <i class="fas fa-search mr-1"></i>Ville, commune ou quartier
                        </label>
                        <div class="relative">
                            <form wire:submit.prevent="search" class="flex gap-2">
                                <input
                                    type="text"
                                    id="searchQuery"
                                    wire:model="searchQuery"
                                    wire:key="searchQuery-{{ $searchQuery }}"
                                    placeholder="Entrez une ville, une commune ou un quartier..."
                                    class="w-full px-4 py-3 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-300"
                                    autocomplete="on">
                                <button type="submit"
                                    class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200 flex items-center justify-center"
                                    aria-label="Lancer la recherche">
                                    <i class="fas fa-search mr-2"></i>Rechercher
                                </button>
                            </form>

                            {{-- Suggestions d'autocomplétion universelles --}}
                            @if($showSuggestions && count($suggestions) > 0)
                            <div class="autocomplete-dropdown absolute w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg shadow-lg max-h-48 overflow-y-auto transition-colors duration-300" wire:ignore.self>
                                @foreach($suggestions as $index => $suggestion)
                                <button type="button"
                                    wire:key="suggestion-{{ $index }}"
                                    wire:click="selectSuggestion('{{ $suggestion }}')"
                                    class="autocomplete-item w-full text-left px-4 py-3 hover:bg-blue-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 flex items-center transition duration-150"
                                    onclick="event.stopPropagation();">
                                    <i class="fas fa-search text-blue-500 mr-3"></i>
                                    <span class="text-gray-900 dark:text-gray-100">{{ $suggestion }}</span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Actions : effacement de la recherche --}}
                    <div class="flex gap-2">
                        {{--
                            Bouton d'effacement de la recherche
                            Affiché seulement quand il y a des résultats de recherche actifs
                        --}}
                        @if($showResults)
                        <button
                            type="button"
                            wire:click="clearSearch"
                            class="w-full bg-gray-500 hover:bg-gray-600 dark:bg-gray-700 dark:hover:bg-gray-600 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center"
                            title="Effacer la recherche">
                            <i class="fas fa-times mr-2"></i>
                            Effacer la recherche
                        </button>
                        @else
                        {{-- Message informatif quand pas de recherche active --}}
                        <div class="w-full text-center py-3 text-gray-500 dark:text-gray-400 text-sm">
                            <i class="fas fa-info-circle mr-1"></i>
                            Les filtres apparaîtront après votre recherche
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Info sur la recherche manuelle --}}
                <div class="mt-3 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        <i class="fas fa-mouse-pointer mr-1"></i>
                        Cliquez sur <span class="font-semibold text-blue-600 dark:text-blue-400">Rechercher</span> pour lancer la recherche
                    </p>
                </div>
            </div>
        </div>
    </div>

    {{-- Section d'affichage des résultats de recherche ou des propriétés populaires --}}
    <div class="container mx-auto mt-8 px-4 bg-white dark:bg-gray-900 rounded-lg shadow-lg transition-colors duration-300">
        {{-- Condition : affichage différent selon si une recherche est active --}}
        @if($showResults)
        {{-- Layout avec sidebar pour les filtres --}}
        <div class="flex flex-col lg:flex-row gap-6">
            {{-- Sidebar des filtres (à gauche) --}}
            <div class="hidden lg:block lg:w-1/4 xl:w-1/5">
                {{-- Filtres permanents dans la sidebar --}}
                <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6 sticky top-24 transition-colors duration-300 border border-gray-200 dark:border-gray-800">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                            <i class="fas fa-sliders-h mr-2 text-blue-600 dark:text-blue-400"></i>
                            Filtres
                        </h3>
                        <button
                            wire:click="clearFilters"
                            class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-white flex items-center transition duration-200">
                            <i class="fas fa-times mr-1"></i>
                            Effacer
                        </button>
                    </div>

                    {{-- Filtres de recherche --}}
                    <div class="space-y-6">
                        {{-- Filtre type de propriété --}}
                        <div>
                            <label for="propertyType" class="block text-sm font-medium text-gray-700 mb-2">
                                <i class="fas fa-home mr-1"></i>Type de logement
                            </label>
                            <select
                                id="propertyType"
                                wire:model.live="propertyType"
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900">
                                <option value="">Tous les types</option>
                                @foreach($propertyTypes as $type)
                                <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Filtre prix --}}
                        @php
                        $user = auth()->user();
                        $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                        @endphp
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                <span class="font-bold mr-1">{{ $userCurrency }}</span>Prix par nuit
                            </label>
                            <div class="grid grid-cols-2 gap-2">
                                <input
                                    type="number"
                                    wire:model.live="minPrice"
                                    placeholder="Min ({{ $userCurrency }})"
                                    min="0"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-300">
                                <input
                                    type="number"
                                    wire:model.live="maxPrice"
                                    placeholder="Max ({{ $userCurrency }})"
                                    min="0"
                                    class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-300">
                            </div>
                        </div>

                        {{-- Filtre nombre de chambres --}}
                        <div>
                            <label for="minRooms" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                <i class="fas fa-bed mr-1"></i>Chambres
                            </label>
                            <select
                                id="minRooms"
                                wire:model.live="minRooms"
                                class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 transition-colors duration-300">
                                <option value="">Peu importe</option>
                                <option value="1">1+ chambre</option>
                                <option value="2">2+ chambres</option>
                                <option value="3">3+ chambres</option>
                                <option value="4">4+ chambres</option>
                                <option value="5">5+ chambres</option>
                            </select>
                        </div>

                        {{-- Commodités --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-3">
                                <i class="fas fa-star mr-1 text-yellow-600"></i>Commodités
                            </label>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($availableAmenities as $amenity)
                                <label class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition duration-150">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedAmenities"
                                        value="{{ $amenity }}"
                                        class="text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                    <span class="text-sm text-gray-700 dark:text-gray-200 flex items-center">
                                        @switch(strtolower($amenity))
                                        @case('wifi')
                                        @case('wi-fi')
                                        <i class="fas fa-wifi text-blue-500 mr-2"></i>
                                        @break
                                        @case('piscine')
                                        @case('pool')
                                        <i class="fas fa-swimming-pool text-blue-500 mr-2"></i>
                                        @break
                                        @case('parking')
                                        <i class="fas fa-parking text-blue-500 mr-2"></i>
                                        @break
                                        @case('climatisation')
                                        @case('air conditioning')
                                        @case('ac')
                                        <i class="fas fa-snowflake text-blue-500 mr-2"></i>
                                        @break
                                        @case('cuisine')
                                        @case('kitchen')
                                        @case('kitchenette')
                                        <i class="fas fa-utensils text-blue-500 mr-2"></i>
                                        @break
                                        @case('télévision')
                                        @case('tv')
                                        @case('television')
                                        <i class="fas fa-tv text-blue-500 mr-2"></i>
                                        @break
                                        @case('jardin')
                                        @case('garden')
                                        <i class="fas fa-leaf text-green-500 mr-2"></i>
                                        @break
                                        @case('balcon')
                                        @case('balcony')
                                        @case('terrasse')
                                        @case('terrace')
                                        <i class="fas fa-door-open text-blue-500 mr-2"></i>
                                        @break
                                        @case('salle de sport')
                                        @case('gym')
                                        @case('fitness')
                                        <i class="fas fa-dumbbell text-blue-500 mr-2"></i>
                                        @break
                                        @case('ascenseur')
                                        @case('elevator')
                                        @case('lift')
                                        <i class="fas fa-elevator text-blue-500 mr-2"></i>
                                        @break
                                        @case('sécurité')
                                        @case('security')
                                        @case('gardien')
                                        <i class="fas fa-shield-alt text-blue-500 mr-2"></i>
                                        @break
                                        @default
                                        <i class="fas fa-check text-green-500 mr-2"></i>
                                        @endswitch
                                        {{ ucfirst($amenity) }}
                                    </span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Info sur les filtres automatiques --}}
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
                            <i class="fas fa-info-circle mr-1"></i>
                            Les filtres s'appliquent automatiquement
                        </div>
                    </div>
                </div>
            </div>

            {{-- Contenu principal (à droite) --}}
            <div class="lg:w-3/4 xl:w-4/5 bg-white dark:bg-gray-900 rounded-lg shadow-lg transition-colors duration-300">
                {{-- En-tête des résultats de recherche avec critères dynamiques --}}
                <div class="mb-6">
                    <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">
                        Résultats de recherche
                        @if($searchQuery)
                        pour <span class="text-blue-600 dark:text-blue-400">"{{ $searchQuery }}"</span>
                        @endif
                    </h2>

                    {{-- Affichage des filtres actifs --}}
                    @if($propertyType || $minPrice || $maxPrice || $minRooms || !empty($selectedAmenities))
                    <div class="mb-3 flex flex-wrap gap-2">
                        @if($propertyType)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                            <i class="fas fa-home mr-1"></i>
                            {{ ucfirst($propertyType) }}
                        </span>
                        @endif

                        @if($minPrice)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300">
                            <span class="font-bold mr-1">{{ $userCurrency }}</span>
                            Min: {{ $minPrice }} {{ $userCurrency }}
                        </span>
                        @endif

                        @if($maxPrice)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-300">
                            <span class="font-bold mr-1">{{ $userCurrency }}</span>
                            Max: {{ $maxPrice }} {{ $userCurrency }}
                        </span>
                        @endif

                        @if($minRooms)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-300">
                            <i class="fas fa-bed mr-1"></i>
                            {{ $minRooms }}+ chambres
                        </span>
                        @endif

                        @if(!empty($selectedAmenities))
                        @foreach($selectedAmenities as $amenity)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-300">
                            <i class="fas fa-star mr-1"></i>
                            {{ ucfirst($amenity) }}
                        </span>
                        @endforeach
                        @endif
                    </div>
                    @endif

                    {{-- Actions de tri et vue mobile --}}
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-4">
                        {{-- Compteur de propriétés trouvées --}}
                        <p class="text-gray-600 dark:text-gray-300 text-lg mb-2 sm:mb-0">{{ count($properties) }} propriété(s) trouvée(s)</p>

                        {{-- Bouton pour afficher les filtres sur mobile --}}
                        <div class="lg:hidden">
                            <button
                                wire:click="toggleFilters"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <i class="fas fa-filter mr-2"></i>
                                Filtres
                                @if($showFilters)
                                <i class="fas fa-chevron-up ml-2"></i>
                                @else
                                <i class="fas fa-chevron-down ml-2"></i>
                                @endif
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Carte des résultats : affichée seulement s'il y a au moins une propriété trouvée --}}
                @if(isset($mapData) && count($properties) > 0)
                <div class="mb-6">
                    <div id="results-map" wire:ignore data-map='@json($mapData)' class="w-full h-80 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-800"></div>
                </div>
                @endif

                {{-- Filtres mobiles (masqués par défaut) --}}
                @if($showFilters)
                <div class="lg:hidden mb-6">
                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-4 border border-gray-200 dark:border-gray-800 transition-colors duration-300">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4 flex items-center">
                            <i class="fas fa-sliders-h mr-2 text-blue-600"></i>
                            Filtres
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            {{-- Filtre type de propriété --}}
                            <div>
                                <label for="propertyTypeMobile" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    <i class="fas fa-home mr-1"></i>Type de logement
                                </label>
                                <select
                                    id="propertyTypeMobile"
                                    wire:model.live="propertyType"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 transition-colors duration-300">
                                    <option value="">Tous les types</option>
                                    @foreach($propertyTypes as $type)
                                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Filtre prix minimum --}}
                            @php
                            $user = auth()->user();
                            $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                            @endphp
                            <div>
                                <label for="minPriceMobile" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    <span class="font-bold mr-1">{{ $userCurrency }}</span>Prix min./nuit
                                </label>
                                <input
                                    type="number"
                                    id="minPriceMobile"
                                    wire:model.live="minPrice"
                                    placeholder="Prix minimum ({{ $userCurrency }})"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-300">
                            </div>

                            {{-- Filtre prix maximum --}}
                            <div>
                                <label for="maxPriceMobile" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    <span class="font-bold mr-1">{{ $userCurrency }}</span>Prix max./nuit
                                </label>
                                <input
                                    type="number"
                                    id="maxPriceMobile"
                                    wire:model.live="maxPrice"
                                    placeholder="Prix maximum ({{ $userCurrency }})"
                                    min="0"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 transition-colors duration-300">
                            </div>

                            {{-- Filtre nombre de chambres --}}
                            <div>
                                <label for="minRoomsMobile" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">
                                    <i class="fas fa-bed mr-1"></i>Chambres
                                </label>
                                <select
                                    id="minRoomsMobile"
                                    wire:model.live="minRooms"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900 dark:bg-gray-800 dark:text-gray-100 transition-colors duration-300">
                                    <option value="">Peu importe</option>
                                    <option value="1">1+ chambre</option>
                                    <option value="2">2+ chambres</option>
                                    <option value="3">3+ chambres</option>
                                    <option value="4">4+ chambres</option>
                                    <option value="5">5+ chambres</option>
                                </select>
                            </div>
                        </div>

                        {{-- Commodités pour mobile --}}
                        <div class="mt-4">
                            <h4 class="text-md font-medium text-gray-900 mb-3 flex items-center">
                                <i class="fas fa-star mr-2 text-yellow-600"></i>
                                Commodités
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-32 overflow-y-auto">
                                @foreach($availableAmenities as $amenity)
                                <label class="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition duration-150">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedAmenities"
                                        value="{{ $amenity }}"
                                        class="text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2">
                                    <span class="text-xs text-gray-700">{{ ucfirst($amenity) }}</span>
                                </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Grid des propriétés trouvées ou message d'absence de résultats --}}
                @if(count($properties) > 0)
                {{-- Grille responsive des propriétés : 1 colonne mobile, 2 tablette, 2 ou 3 desktop selon la sidebar --}}
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    {{-- Boucle sur chaque propriété trouvée --}}
                    @foreach($properties as $property)
                    {{-- Carte de propriété avec effet hover --}}
                    <div class="shadow-lg dark:shadow-lg rounded-lg overflow-hidden hover:shadow-xl dark:hover:shadow-2xl transition duration-200 bg-white dark:bg-gray-900">
                        {{-- Statut de la propriété --}}
                        @php
                        $isOccupied = $property->bookings()->where('status', 'accepted')
                        ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->exists();
                            @endphp
                            <div class="absolute top-3 left-3 z-10">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $isOccupied ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-800' }}">
                                    <i class="fas {{ $isOccupied ? 'fa-lock' : 'fa-unlock' }} mr-1"></i>
                                    {{ $isOccupied ? 'Occupé' : 'Disponible' }}
                                </span>
                            </div>
                            {{-- Image de la propriété avec lien : première image ou image par défaut --}}
                            <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                class="block"
                                aria-label="Réserver {{ $property->name }}">
                                @if($property->firstImage())
                                <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}"
                                    alt="{{ $property->name }}"
                                    class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300">
                                @else
                                <img src="{{ asset('images/default-image.jpg') }}"
                                    alt="{{ $property->name ?? 'propriété' }}"
                                    class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300">
                                @endif
                            </a>

                            {{-- Contenu de la carte --}}
                            <div class="p-4">
                                {{-- Nom de la propriété avec lien --}}
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                    <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                        class="hover:text-blue-600 transition-colors duration-200"
                                        aria-label="Réserver {{ $property->name }}">
                                        {{ $property->name ?? 'Nom non disponible' }}
                                    </a>
                                </h3>

                                {{-- Localisation : ville et quartier --}}
                                <p class="text-gray-600 dark:text-gray-300 mb-2">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    {{ $property->city ?? 'Ville non disponible' }}
                                    @if($property->municipality)
                                    , {{ $property->municipality }}
                                    @endif
                                </p>

                                {{-- Type de logement --}}
                                @if($property->property_type)
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                                        <i class="fas fa-home mr-1"></i>
                                        {{ ucfirst($property->property_type) }}
                                    </span>
                                    {{-- Nombre de chambres --}}
                                    @if($property->number_of_rooms)
                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                        <i class="fas fa-bed mr-1"></i>
                                        {{ $property->number_of_rooms }} chambre{{ $property->number_of_rooms > 1 ? 's' : '' }}
                                    </span>
                                    @endif
                                </div>
                                @elseif($property->number_of_rooms)
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                        <i class="fas fa-bed mr-1"></i>
                                        {{ $property->number_of_rooms }} chambre{{ $property->number_of_rooms > 1 ? 's' : '' }}
                                    </span>
                                </div>
                                @endif

                                {{-- Description tronquée --}}
                                <p class="text-gray-500 dark:text-gray-300 mb-3">
                                    {{ Str::words($property->description ?? 'Description non disponible', 15, '...') }}
                                </p>

                                {{-- Commodités principales --}}
                                @if($property->features && count($property->features) > 0)
                                <div class="mb-3 flex flex-wrap gap-1">
                                    @foreach(array_slice($property->features, 0, 3) as $feature)
                                    <span class="inline-flex items-center px-2 py-1 bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-200 rounded text-xs">
                                        @switch(strtolower($feature))
                                        @case('wifi')
                                        @case('wi-fi')
                                        <i class="fas fa-wifi mr-1"></i>
                                        @break
                                        @case('piscine')
                                        @case('pool')
                                        <i class="fas fa-swimming-pool mr-1"></i>
                                        @break
                                        @case('parking')
                                        <i class="fas fa-parking mr-1"></i>
                                        @break
                                        @case('climatisation')
                                        @case('air conditioning')
                                        @case('ac')
                                        <i class="fas fa-snowflake mr-1"></i>
                                        @break
                                        @default
                                        <i class="fas fa-check mr-1"></i>
                                        @endswitch
                                        {{ ucfirst($feature) }}
                                    </span>
                                    @endforeach
                                    @if(count($property->features) > 3)
                                    <span class="inline-flex items-center px-2 py-1 bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded text-xs">
                                        +{{ count($property->features) - 3 }} autres
                                    </span>
                                    @endif
                                </div>
                                @endif

                                {{-- Ligne de bas : prix et bouton de réservation --}}
                                <div class="flex justify-between items-center">
                                    {{-- Prix par nuit (converti) --}}
                                    @php
                                    $user = auth()->user();
                                    $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                                    $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
                                    $converted = $rate ? round($property->price_per_night * $rate, 2) : $property->price_per_night;
                                    @endphp
                                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400">
                                        {{ number_format($converted, 2) }} {{ $userCurrency }}/nuit
                                    </span>
                                </div>
                            </div>
                    </div>
                    @endforeach
                </div>
                @else
                {{-- Message affiché quand aucune propriété ne correspond aux critères --}}
                <div class="text-center py-12 bg-white dark:bg-gray-900 rounded-lg shadow-lg transition-colors duration-300">
                    <div class="text-gray-400 dark:text-gray-500 mb-4">
                        <i class="fas fa-search text-6xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-600 dark:text-gray-200 mb-2">Aucune propriété trouvée</h3>
                    <p class="text-gray-500 dark:text-gray-400 mb-4">
                        Essayez de modifier vos critères de recherche ou explorez toutes nos propriétés
                    </p>
                    {{-- Bouton pour effacer la recherche et voir toutes les propriétés --}}
                    <button wire:click="clearSearch"
                        class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200">
                        Voir toutes les propriétés
                    </button>
                </div>
                @endif
            </div>
        </div>
        @else
        {{-- Affichage par défaut : propriétés populaires avec carrousel Swiper optimisé --}}
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">Nos propriétés populaires</h2>
            <p class="text-gray-600 dark:text-gray-300">Découvrez les hébergements les plus appréciés en Côte d'Ivoire</p>
        </div>

        {{-- Conteneur Swiper optimisé pour le carrousel des propriétés --}}
        <div class="swiper-container property-carousel max-w-full mx-auto relative bg-white dark:bg-gray-900 dark:shadow-lg rounded-lg transition-colors duration-300"
            data-swiper-slides="{{ count($properties) }}"
            wire:key="property-carousel-{{ md5(json_encode($properties->pluck('id')->toArray())) }}">

            {{-- Wrapper contenant les slides --}}
            <div class="swiper-wrapper">
                {{-- Boucle sur toutes les propriétés pour créer les slides --}}
                @foreach($properties as $index => $property)
                <div class="swiper-slide" data-swiper-slide-index="{{ $index }}">
                    {{-- Carte de propriété optimisée dans le carrousel --}}
                    <div class="shadow-md dark:shadow-lg rounded-lg overflow-hidden max-w-md w-full h-full hover:shadow-lg dark:hover:shadow-2xl transition-shadow duration-300 mx-auto relative bg-white dark:bg-gray-900 flex flex-col">
                        {{-- Statut de la propriété --}}
                        @php
                        $isOccupied = $property->bookings()->where('status', 'accepted')
                        ->whereDate('start_date', '<=', now())
                            ->whereDate('end_date', '>=', now())
                            ->exists();
                            @endphp
                            <div class="absolute top-3 left-3 z-10">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $isOccupied ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-800' }}">
                                    <i class="fas {{ $isOccupied ? 'fa-lock' : 'fa-unlock' }} mr-1"></i>
                                    {{ $isOccupied ? 'Occupé' : 'Disponible' }}
                                </span>
                            </div>

                            {{-- Container d'image avec lazy loading et lien vers booking --}}
                            <div class="relative overflow-hidden">
                                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                    class="block w-full h-full"
                                    aria-label="Réserver {{ $property->name }}">
                                    @if($property->firstImage())
                                    <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}"
                                        alt="{{ $property->name }}"
                                        class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
                                        loading="{{ $index < 3 ? 'eager' : 'lazy' }}"
                                        decoding="async">
                                    @else
                                    <img src="{{ asset('images/default-image.jpg') }}"
                                        alt="{{ $property->name ?? 'propriété' }}"
                                        class="w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
                                        loading="{{ $index < 3 ? 'eager' : 'lazy' }}"
                                        decoding="async">
                                    @endif
                                </a>
                            </div> {{-- Contenu de la carte optimisé --}}
                            <div class="flex flex-col flex-1 p-4 h-80 overflow-hidden">
                                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-1 truncate">
                                    <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}" class="hover:text-blue-600 transition-colors duration-200">
                                        {{ $property->name ?? 'Nom non disponible' }}
                                    </a>
                                </h3>
                                <div class="flex items-center text-gray-600 dark:text-gray-300 text-sm mb-2">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    {{ $property->city }}@if($property->municipality), {{ $property->municipality }}@endif
                                </div>
                                @if($property->property_type || $property->number_of_rooms)
                                <div class="mb-2 flex flex-wrap gap-2">
                                    @if($property->property_type)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-home mr-1"></i>
                                        {{ ucfirst($property->property_type) }}
                                    </span>
                                    @endif
                                    @if($property->number_of_rooms)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <i class="fas fa-bed mr-1"></i>
                                        {{ $property->number_of_rooms }} chambre{{ $property->number_of_rooms > 1 ? 's' : '' }}
                                    </span>
                                    @endif
                                </div>
                                @endif
                                <p class="text-gray-500 dark:text-gray-300 text-sm mb-2 line-clamp-2">
                                    {{ Str::words($property->description ?? 'Description non disponible', 15, '...') }}
                                </p>
                                @if($property->features && count($property->features) > 0)
                                <div class="mb-2 flex flex-wrap gap-1 min-h-[24px]">
                                    @foreach(array_slice($property->features, 0, 2) as $feature)
                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-200 rounded text-xs">
                                        @switch(strtolower($feature))
                                        @case('wifi')
                                        @case('wi-fi')
                                        <i class="fas fa-wifi mr-1"></i>
                                        @break
                                        @case('piscine')
                                        @case('pool')
                                        <i class="fas fa-swimming-pool mr-1"></i>
                                        @break
                                        @case('parking')
                                        <i class="fas fa-parking mr-1"></i>
                                        @break
                                        @case('climatisation')
                                        @case('air conditioning')
                                        @case('ac')
                                        <i class="fas fa-snowflake mr-1"></i>
                                        @break
                                        @default
                                        <i class="fas fa-check mr-1"></i>
                                        @endswitch
                                        {{ ucfirst($feature) }}
                                    </span>
                                    @endforeach
                                    @if(count($property->features) > 2)
                                    <span class="inline-flex items-center px-1.5 py-0.5 bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded text-xs">
                                        +{{ count($property->features) - 2 }} autres
                                    </span>
                                    @endif
                                </div>
                                @endif
                                <div class="flex items-center justify-between mt-auto pt-2">
                                    @php
                                    $user = auth()->user();
                                    $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                                    $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
                                    $converted = $rate ? round($property->price_per_night * $rate, 2) : $property->price_per_night;
                                    @endphp
                                    <span class="text-lg font-bold text-blue-600">
                                        {{ number_format($converted, 2) }} {{ $userCurrency }} / nuit
                                    </span>
                                </div>
                            </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Navigation du carrousel optimisée --}}
            @if(count($properties) > 1)
            <div class="swiper-navigation hidden sm:flex">
                {{-- Bouton précédent --}}
                <button class="swiper-button-prev carousel-nav-btn"
                    type="button"
                    aria-label="Propriété précédente">
                    <i class="fas fa-chevron-left" aria-hidden="true"></i>
                </button>

                {{-- Bouton suivant --}}
                <button class="swiper-button-next carousel-nav-btn"
                    type="button"
                    aria-label="Propriété suivante">
                    <i class="fas fa-chevron-right" aria-hidden="true"></i>
                </button>
            </div>

            {{-- Pagination dots --}}
            <div class="swiper-pagination mt-6"></div>
            @endif

            {{-- Indicateur de chargement --}}
            <div class="carousel-loading hidden">
                <div class="flex justify-center items-center py-8">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                </div>
            </div>
        </div>

        {{-- Section carrousel des villes populaires --}}
        <div class="mt-16">
            <div class="mb-6">
                <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">Explorez par ville</h2>
                <p class="text-gray-600 dark:text-gray-300">Découvrez les destinations les plus populaires en Côte d'Ivoire</p>
            </div>

            {{-- Conteneur Swiper pour les villes --}}
            <div class="swiper-container cities-carousel max-w-full mx-auto relative bg-white dark:bg-gray-900 dark:shadow-lg rounded-lg transition-colors duration-300"
                data-swiper-slides="{{ count($popularCities) }}"
                wire:key="cities-carousel-{{ md5(json_encode($popularCities->pluck('city')->toArray())) }}">

                {{-- Wrapper contenant les slides des villes --}}
                <div class="swiper-wrapper">
                    @foreach($popularCities as $index => $cityData)
                    <div class="swiper-slide" data-swiper-slide-index="{{ $index }}">
                        {{-- Carte de ville --}}
                        <div class="city-card bg-white dark:bg-gray-900 shadow-md dark:shadow-lg rounded-lg overflow-hidden w-full h-full hover:shadow-lg dark:hover:shadow-2xl transition-shadow duration-300 cursor-pointer"
                            wire:click="searchByCity('{{ $cityData->city }}')">

                            {{-- Image de ville (issue d’un logement populaire si dispo) --}}
                            <div class="city-image-container relative overflow-hidden">
                                @php $cityImage = $cityData->city_image_url ?? null; @endphp
                                @if($cityImage)
                                <div class="relative w-full h-48">
                                    <img src="{{ $cityImage }}" alt="{{ $cityData->city }}" class="w-full h-48 object-cover">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                                    <div class="absolute bottom-3 left-3 text-white drop-shadow">
                                        <i class="fas fa-city text-2xl mb-1"></i>
                                        <h3 class="text-lg font-bold">{{ $cityData->city }}</h3>
                                    </div>
                                </div>
                                @else
                                <div class="city-image w-full h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <i class="fas fa-city text-4xl mb-2"></i>
                                        <h3 class="text-xl font-bold">{{ $cityData->city }}</h3>
                                    </div>
                                </div>
                                @endif

                                {{-- Badge nombre de propriétés --}}
                                <div class="absolute top-3 right-3 bg-white text-blue-600 px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                    {{ $cityData->properties_count }} propriété{{ $cityData->properties_count > 1 ? 's' : '' }}
                                </div>
                            </div>

                            {{-- Contenu de la carte ville --}}
                            <div class="city-content p-4">
                                <h3 class="city-title text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                    {{ $cityData->city }}
                                </h3>
                                <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">
                                    {{ $cityData->properties_count }} hébergement{{ $cityData->properties_count > 1 ? 's' : '' }} disponible{{ $cityData->properties_count > 1 ? 's' : '' }}
                                </p>
                                <div class="city-cta">
                                    <span class="inline-flex items-center text-blue-600 dark:text-blue-300 text-sm font-medium hover:text-blue-700 dark:hover:text-blue-400 transition-colors duration-200">
                                        <i class="fas fa-arrow-right mr-2"></i>
                                        Découvrir
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Navigation du carrousel des villes --}}
                @if(count($popularCities) > 1)
                <div class="swiper-navigation hidden sm:flex">
                    {{-- Bouton précédent --}}
                    <button class="swiper-button-prev cities-nav-btn"
                        type="button"
                        aria-label="Ville précédente">
                        <i class="fas fa-chevron-left" aria-hidden="true"></i>
                    </button>

                    {{-- Bouton suivant --}}
                    <button class="swiper-button-next cities-nav-btn"
                        type="button"
                        aria-label="Ville suivante">
                        <i class="fas fa-chevron-right" aria-hidden="true"></i>
                    </button>
                </div>

                {{-- Pagination dots pour les villes --}}
                <div class="swiper-pagination cities-pagination mt-6"></div>
                @endif
            </div>
        </div>

        {{-- Section des hébergements les plus visités par ville --}}
        @if(!empty($topPropertiesByCity))
        <div class="mt-16">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-800 dark:text-gray-100 mb-2">Hébergements les plus visités</h2>
                <p class="text-gray-600 dark:text-gray-300">Découvrez les propriétés les plus populaires dans chaque ville</p>
            </div>

            @foreach($topPropertiesByCity as $cityName => $cityProperties)
            <div class="mb-12">
                {{-- En-tête de la ville --}}
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-semibold text-gray-800 dark:text-gray-100 flex items-center">
                        <i class="fas fa-city text-blue-600 mr-3"></i>
                        {{ $cityName }}
                        <span class="ml-2 text-sm font-normal text-gray-500">
                            ({{ count($cityProperties) }} propriété{{ count($cityProperties) > 1 ? 's' : '' }} populaire{{ count($cityProperties) > 1 ? 's' : '' }})
                        </span>
                    </h3>
                    <button
                        wire:click="searchByCity('{{ $cityName }}')"
                        class="text-blue-600 hover:text-blue-700 text-sm font-medium flex items-center transition duration-200">
                        Voir tous les hébergements
                        <i class="fas fa-arrow-right ml-1"></i>
                    </button>
                </div>

                {{-- Grille des propriétés populaires de cette ville --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($cityProperties as $property)
                    <div class="popular-property-card bg-white dark:bg-gray-900 shadow-lg dark:shadow-lg rounded-lg overflow-hidden hover:shadow-xl dark:hover:shadow-2xl transition-shadow duration-300 relative">
                        {{-- Badge "Populaire" --}}
                        <div class="absolute top-3 left-3 z-10 flex flex-col gap-1">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                <i class="fas fa-fire mr-1"></i>
                                Populaire
                            </span>
                            @php
                            $isOccupied = $property->bookings()->where('status', 'accepted')
                            ->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now())
                                ->exists();
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $isOccupied ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-800' }}">
                                    <i class="fas {{ $isOccupied ? 'fa-lock' : 'fa-unlock' }} mr-1"></i>
                                    {{ $isOccupied ? 'Occupé' : 'Disponible' }}
                                </span>
                        </div>

                        {{-- Bouton wishlist (j'aime) --}}
                        <div class="absolute top-3 right-3 z-10">
                            <button
                                @if(auth()->check())
                                wire:click.stop="toggleWishlist({{ $property->id }})"
                                @else
                                onclick="window.dispatchEvent(new CustomEvent('show-login-modal'))"
                                @endif
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-pink-100 text-pink-600 hover:bg-pink-200 transition"
                                title="Ajouter ou retirer de la liste de souhaits">
                                @if(auth()->check() && auth()->user()->wishlists->contains('property_id', $property->id))
                                <i class="fas fa-heart"></i>
                                @else
                                <i class="far fa-heart"></i>
                                @endif
                            </button>
                        </div>

                        {{-- Image de la propriété --}}
                        <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                            class="block"
                            aria-label="Réserver {{ $property->name }}">
                            @if($property->firstImage())
                            <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}"
                                alt="{{ $property->name }}"
                                class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300">
                            @else
                            <img src="{{ asset('images/default-image.jpg') }}"
                                alt="{{ $property->name ?? 'propriété' }}"
                                class="w-full h-48 object-cover hover:scale-105 transition-transform duration-300">
                            @endif
                        </a>

                        {{-- Contenu de la carte --}}
                        <div class="p-4">
                            {{-- Nom de la propriété --}}
                            <h4 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-2">
                                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                    class="hover:text-blue-600 transition-colors duration-200"
                                    aria-label="Réserver {{ $property->name }}">
                                    {{ $property->name ?? 'Nom non disponible' }}
                                </a>
                            </h4>

                            {{-- Localisation --}}
                            <p class="text-gray-600 dark:text-gray-300 mb-2 flex items-center">
                                <i class="fas fa-map-marker-alt mr-1"></i>
                                {{ $property->city }}
                                @if($property->municipality)
                                , {{ $property->municipality }}
                                @endif
                            </p>

                            {{-- Type de logement et nombre de chambres --}}
                            @if($property->property_type || $property->number_of_rooms)
                            <div class="mb-3 flex flex-wrap gap-2">
                                @if($property->property_type)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-300">
                                    <i class="fas fa-home mr-1"></i>
                                    {{ ucfirst($property->property_type) }}
                                </span>
                                @endif
                                @if($property->number_of_rooms)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200">
                                    <i class="fas fa-bed mr-1"></i>
                                    {{ $property->number_of_rooms }} chambre{{ $property->number_of_rooms > 1 ? 's' : '' }}
                                </span>
                                @endif
                            </div>
                            @endif

                            {{-- Commodités principales --}}
                            @if($property->features && count($property->features) > 0)
                            <div class="mb-3 flex flex-wrap gap-1">
                                @foreach(array_slice($property->features, 0, 2) as $feature)
                                <span class="inline-flex items-center px-2 py-1 bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-200 rounded text-xs">
                                    @switch(strtolower($feature))
                                    @case('wifi')
                                    @case('wi-fi')
                                    <i class="fas fa-wifi mr-1"></i>
                                    @break
                                    @case('piscine')
                                    @case('pool')
                                    <i class="fas fa-swimming-pool mr-1"></i>
                                    @break
                                    @case('parking')
                                    <i class="fas fa-parking mr-1"></i>
                                    @break
                                    @case('climatisation')
                                    @case('air conditioning')
                                    @case('ac')
                                    <i class="fas fa-snowflake mr-1"></i>
                                    @break
                                    @default
                                    <i class="fas fa-check mr-1"></i>
                                    @endswitch
                                    {{ ucfirst($feature) }}
                                </span>
                                @endforeach
                                @if(count($property->features) > 2)
                                <span class="inline-flex items-center px-2 py-1 bg-blue-50 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded text-xs">
                                    +{{ count($property->features) - 2 }} autres
                                </span>
                                @endif
                            </div>
                            @endif

                            {{-- Note moyenne, prix et bouton de réservation --}}
                            <div class="flex justify-between items-center mt-4">

                                @php
                                $user = auth()->user();
                                $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
                                $rate = app('App\\Livewire\\BookingManager')->getExchangeRate('XOF', $userCurrency);
                                $converted = $rate ? round($property->price_per_night * $rate, 2) : $property->price_per_night;
                                @endphp
                                <span class="text-lg font-bold text-blue-600">
                                    {{ number_format($converted, 2) }} {{ $userCurrency }}/nuit
                                </span>
                                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                    class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 ml-2">
                                    <i class="fas fa-calendar-check mr-1"></i>
                                    Réserver
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif
        @endif
    </div>
</div>