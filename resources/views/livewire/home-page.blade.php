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
    <div class="hero-section relative bg-cover bg-center bg-no-repeat text-white">
        <div class="container mx-auto py-16 px-4">
            {{-- Titre et sous-titre de bienvenue --}}
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-4">Bienvenue sur Afridays</h1>
                <p class="text-xl text-blue-100">Trouvez la propriété idéale pour votre séjour en Côte d'Ivoire</p>
            </div>

            {{--
                Formulaire de recherche avec autocomplétion
                - Recherche par ville avec suggestions prédéfinies + BDD
                - Recherche par quartier/municipality depuis la BDD
                - Boutons de recherche et d'effacement conditionnels
            --}}
            <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-6">
                <form wire:submit.prevent="search" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">

                    {{-- Champ de recherche par ville --}}
                    <div>
                        <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-map-marker-alt mr-1"></i>Ville
                        </label>
                        <div class="relative">
                            {{--
                                Input de recherche ville avec modèle Livewire en temps réel
                                wire:model.live déclenche la recherche à chaque caractère saisi
                            --}}
                            <input
                                type="text"
                                id="city"
                                wire:model.live="searchCity"
                                placeholder="Entrez une ville de Côte d'Ivoire..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                                autocomplete="off">

                            {{--
                                Suggestions d'autocomplétion pour les villes
                                Affichage conditionnel : seulement si il y a des suggestions à montrer
                                wire:ignore.self empêche Livewire de re-render ce conteneur
                            --}}
                            @if($showCitySuggestions && count($citySuggestions) > 0)
                            <div class="autocomplete-dropdown absolute w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                                wire:ignore.self>
                                {{--
                                    Boucle sur les suggestions de villes
                                    wire:key pour un tracking optimal par Livewire
                                    wire:click pour la sélection de la suggestion
                                --}}
                                @foreach($citySuggestions as $index => $suggestion)
                                <button type="button"
                                    wire:key="city-{{ $index }}"
                                    wire:click="selectCity('{{ $suggestion }}')"
                                    class="autocomplete-item w-full text-left px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-center transition duration-150"
                                    onclick="event.stopPropagation();">
                                    {{-- Icône de localisation --}}
                                    <i class="fas fa-map-marker-alt text-blue-500 mr-3"></i>
                                    {{-- Nom de la ville --}}
                                    <span class="text-gray-900">{{ $suggestion }}</span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Champ de recherche par quartier/municipality --}}
                    <div>
                        <label for="municipality" class="block text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-building mr-1"></i>Quartier
                        </label>
                        <div class="relative">
                            {{--
                                Input de recherche quartier avec modèle Livewire en temps réel
                                Les suggestions proviennent de la base de données (table properties)
                            --}}
                            <input
                                type="text"
                                id="municipality"
                                wire:model.live="searchMunicipality"
                                placeholder="Entrez un quartier..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-gray-900"
                                autocomplete="off">

                            {{-- Suggestions d'autocomplétion pour les quartiers --}}
                            @if($showMunicipalitySuggestions && count($municipalitySuggestions) > 0)
                            <div class="autocomplete-dropdown absolute w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto">
                                {{-- Boucle sur les suggestions de quartiers depuis la BDD --}}
                                @foreach($municipalitySuggestions as $index => $suggestion)
                                <button type="button"
                                    wire:key="municipality-{{ $index }}"
                                    wire:click="selectMunicipality('{{ $suggestion }}')"
                                    class="autocomplete-item w-full text-left px-4 py-3 hover:bg-blue-50 cursor-pointer border-b border-gray-100 last:border-b-0 flex items-center transition duration-150"
                                    onclick="event.stopPropagation();">
                                    {{-- Icône de bâtiment pour les quartiers --}}
                                    <i class="fas fa-building text-blue-500 mr-3"></i>
                                    {{-- Nom du quartier --}}
                                    <span class="text-gray-900">{{ $suggestion }}</span>
                                </button>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Boutons d'action : recherche et effacement --}}
                    <div class="flex gap-2">
                        {{-- Bouton de recherche principal --}}
                        <button
                            type="submit"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center">
                            <i class="fas fa-search mr-2"></i>
                            Rechercher
                        </button>

                        {{--
                            Bouton d'effacement de la recherche
                            Affiché seulement quand il y a des résultats de recherche actifs
                        --}}
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

    {{-- Section d'affichage des résultats de recherche ou des propriétés populaires --}}
    <div class="container mx-auto mt-8 px-4">
        {{-- Condition : affichage différent selon si une recherche est active --}}
        @if($showResults)
        {{-- En-tête des résultats de recherche avec critères dynamiques --}}
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">
                Résultats de recherche
                {{-- Affichage conditionnel des critères de recherche --}}
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
            {{-- Compteur de propriétés trouvées --}}
            <p class="text-gray-600 text-lg">{{ count($properties) }} propriété(s) trouvée(s)</p>
        </div>

        {{-- Grid des propriétés trouvées ou message d'absence de résultats --}}
        @if(count($properties) > 0)
        {{-- Grille responsive des propriétés : 1 colonne mobile, 2 tablette, 3 desktop --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {{-- Boucle sur chaque propriété trouvée --}}
            @foreach($properties as $property)
            {{-- Carte de propriété avec effet hover --}}
            <div class="shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition duration-200">
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
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                            class="hover:text-blue-600 transition-colors duration-200"
                            aria-label="Réserver {{ $property->name }}">
                            {{ $property->name ?? 'Nom non disponible' }}
                        </a>
                    </h3>

                    {{-- Localisation : ville et quartier --}}
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        {{ $property->city ?? 'Ville non disponible' }}
                        @if($property->municipality)
                        , {{ $property->municipality }}
                        @endif
                    </p>

                    {{-- Type de logement --}}
                    @if($property->property_type)
                    <div class="mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-home mr-1"></i>
                            {{ ucfirst($property->property_type) }}
                        </span>
                    </div>
                    @endif

                    {{-- Description tronquée --}}
                    <p class="text-gray-500 mb-4">
                        {{ Str::words($property->description ?? 'Description non disponible', 15, '...') }}
                    </p>

                    {{-- Ligne de bas : prix et bouton de réservation --}}
                    <div class="flex justify-between items-center">
                        {{-- Prix par nuit --}}
                        <span class="text-lg font-bold text-blue-600">
                            {{ $property->price_per_night ?? 'Prix non disponible' }} €/nuit
                        </span>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        {{-- Message affiché quand aucune propriété ne correspond aux critères --}}
        <div class="text-center py-12">
            <div class="text-gray-400 mb-4">
                <i class="fas fa-search text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-600 mb-2">Aucune propriété trouvée</h3>
            <p class="text-gray-500 mb-4">
                Essayez de modifier vos critères de recherche ou explorez toutes nos propriétés
            </p>
            {{-- Bouton pour effacer la recherche et voir toutes les propriétés --}}
            <button wire:click="clearSearch"
                class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-lg transition duration-200">
                Voir toutes les propriétés
            </button>
        </div>
        @endif
        @else
        {{-- Affichage par défaut : propriétés populaires avec carrousel Swiper optimisé --}}
        <div class="mb-6">
            <h2 class="text-3xl font-bold text-gray-800 mb-2">Nos propriétés populaires</h2>
            <p class="text-gray-600">Découvrez les hébergements les plus appréciés en Côte d'Ivoire</p>
        </div>

        {{-- Conteneur Swiper optimisé pour le carrousel des propriétés --}}
        <div class="swiper-container property-carousel max-w-full mx-auto relative"
            data-swiper-slides="{{ count($properties) }}">

            {{-- Wrapper contenant les slides --}}
            <div class="swiper-wrapper">
                {{-- Boucle sur toutes les propriétés pour créer les slides --}}
                @foreach($properties as $index => $property)
                <div class="swiper-slide" data-swiper-slide-index="{{ $index }}">
                    {{-- Carte de propriété optimisée dans le carrousel --}}
                    <div class="property-card shadow-md rounded-lg overflow-hidden w-full h-full hover:shadow-lg transition-shadow duration-300">

                        {{-- Container d'image avec lazy loading et lien vers booking --}}
                        <div class="property-image-container relative overflow-hidden">
                            <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                class="block w-full h-full"
                                aria-label="Réserver {{ $property->name }}">
                                @if($property->firstImage())
                                <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}"
                                    alt="{{ $property->name }}"
                                    class="property-image w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
                                    loading="{{ $index < 3 ? 'eager' : 'lazy' }}"
                                    decoding="async">
                                @else
                                <img src="{{ asset('images/default-image.jpg') }}"
                                    alt="{{ $property->name ?? 'propriété' }}"
                                    class="property-image w-full h-48 object-cover transition-transform duration-300 hover:scale-105"
                                    loading="{{ $index < 3 ? 'eager' : 'lazy' }}"
                                    decoding="async">
                                @endif
                            </a>

                            {{-- Badge de prix en overlay --}}
                            <div class="absolute top-3 right-3 bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                {{ $property->price_per_night ?? 'N/A' }} €/nuit
                            </div>
                        </div> {{-- Contenu de la carte optimisé --}}
                        <div class="property-content p-4 flex flex-col h-full">
                            {{-- En-tête avec nom et localisation --}}
                            <div class="property-header mb-3">
                                <h3 class="property-title text-lg font-semibold text-gray-800 mb-1 line-clamp-1">
                                    <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                        class="hover:text-blue-600 transition-colors duration-200"
                                        aria-label="Réserver {{ $property->name }}">
                                        {{ $property->name ?? 'Nom non disponible' }}
                                    </a>
                                </h3>

                                <div class="property-location flex items-center text-gray-600 text-sm">
                                    <i class="fas fa-map-marker-alt text-blue-500 mr-1 flex-shrink-0" aria-hidden="true"></i>
                                    <span class="line-clamp-1">
                                        {{ $property->city ?? 'Ville non disponible' }}@if($property->district), {{ $property->district }}@endif
                                    </span>
                                </div>

                                {{-- Type de logement dans le carrousel --}}
                                @if($property->property_type)
                                <div class="mt-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-home mr-1"></i>
                                        {{ ucfirst($property->property_type) }}
                                    </span>
                                </div>
                                @endif
                            </div>

                            {{-- Description avec limitation de lignes --}}
                            <div class="property-description flex-grow mb-4">
                                <p class="text-gray-500 text-sm line-clamp-3">
                                    {{ $property->description ?? 'Description non disponible' }}
                                </p>
                            </div>

                            {{-- Pied de carte avec bouton de réservation --}}
                            <div class="property-footer mt-auto">
                                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                    class="property-cta inline-flex items-center justify-center w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium py-2.5 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                    aria-label="Réserver {{ $property->name }}">
                                    <i class="fas fa-calendar-check mr-2" aria-hidden="true"></i>
                                    Réserver maintenant
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Navigation du carrousel optimisée --}}
            @if(count($properties) > 1)
            <div class="swiper-navigation">
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
                <h2 class="text-3xl font-bold text-gray-800 mb-2">Explorez par ville</h2>
                <p class="text-gray-600">Découvrez les destinations les plus populaires en Côte d'Ivoire</p>
            </div>

            {{-- Conteneur Swiper pour les villes --}}
            <div class="swiper-container cities-carousel max-w-full mx-auto relative"
                data-swiper-slides="{{ count($popularCities) }}">

                {{-- Wrapper contenant les slides des villes --}}
                <div class="swiper-wrapper">
                    @foreach($popularCities as $index => $cityData)
                    <div class="swiper-slide" data-swiper-slide-index="{{ $index }}">
                        {{-- Carte de ville --}}
                        <div class="city-card bg-white shadow-md rounded-lg overflow-hidden w-full h-full hover:shadow-lg transition-shadow duration-300 cursor-pointer"
                            wire:click="searchByCity('{{ $cityData->city }}')">

                            {{-- Image de ville (placeholder pour le moment) --}}
                            <div class="city-image-container relative overflow-hidden">
                                <div class="city-image w-full h-48 bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center">
                                    <div class="text-center text-white">
                                        <i class="fas fa-city text-4xl mb-2"></i>
                                        <h3 class="text-xl font-bold">{{ $cityData->city }}</h3>
                                    </div>
                                </div>

                                {{-- Badge nombre de propriétés --}}
                                <div class="absolute top-3 right-3 bg-white text-blue-600 px-3 py-1 rounded-full text-sm font-semibold shadow-lg">
                                    {{ $cityData->properties_count }} propriété{{ $cityData->properties_count > 1 ? 's' : '' }}
                                </div>
                            </div>

                            {{-- Contenu de la carte ville --}}
                            <div class="city-content p-4">
                                <h3 class="city-title text-lg font-semibold text-gray-800 mb-2">
                                    {{ $cityData->city }}
                                </h3>
                                <p class="text-gray-600 text-sm mb-3">
                                    {{ $cityData->properties_count }} hébergement{{ $cityData->properties_count > 1 ? 's' : '' }} disponible{{ $cityData->properties_count > 1 ? 's' : '' }}
                                </p>
                                <div class="city-cta">
                                    <span class="inline-flex items-center text-blue-600 text-sm font-medium hover:text-blue-700 transition-colors duration-200">
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
                <div class="swiper-navigation">
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
        @endif
    </div>
</div>