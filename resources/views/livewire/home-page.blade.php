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
    <div class="relative bg-cover bg-center bg-no-repeat text-white" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('{{ asset('images/photo5.jpg') }}');">
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
                                style="z-index: 9999;" wire:ignore.self>
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
                            <div class="autocomplete-dropdown absolute w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg max-h-48 overflow-y-auto"
                                style="z-index: 9999;">
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
            <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition duration-200">
                {{-- Image de la propriété : première image ou image par défaut --}}
                @if($property->firstImage())
                <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}"
                    alt="{{ $property->name }}"
                    class="w-full h-48 object-cover">
                @else
                <img src="{{ asset('images/default-image.jpg') }}"
                    alt="Propriété par défaut"
                    class="w-full h-48 object-cover">
                @endif

                {{-- Contenu de la carte --}}
                <div class="p-4">
                    {{-- Nom de la propriété --}}
                    <h3 class="text-lg font-semibold text-gray-800 mb-2">
                        {{ $property->name ?? 'Nom non disponible' }}
                    </h3>

                    {{-- Localisation : ville et quartier --}}
                    <p class="text-gray-600 mb-2">
                        <i class="fas fa-map-marker-alt mr-1"></i>
                        {{ $property->city ?? 'Ville non disponible' }}
                        @if($property->municipality)
                        , {{ $property->municipality }}
                        @endif
                    </p>

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

                        {{-- Bouton de réservation --}}
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
        {{-- Affichage par défaut : propriétés populaires avec carrousel Swiper --}}
        <h2 class="text-3xl font-bold mb-6 text-gray-800">Nos propriétés populaires</h2>

        {{-- Conteneur Swiper pour le carrousel des propriétés --}}
        <div class="swiper-container max-w-full mx-auto relative">
            {{-- Wrapper contenant les slides --}}
            <div class="swiper-wrapper">
                {{-- Boucle sur toutes les propriétés pour créer les slides --}}
                @foreach($properties as $property)
                <div class="swiper-slide">
                    {{-- Carte de propriété dans le carrousel --}}
                    <div class="bg-white shadow-md rounded-lg overflow-hidden w-full h-full">
                        {{-- Image de la propriété --}}
                        @if($property->firstImage())
                        <img src="{{ asset('storage/' . $property->firstImage()->image_path) }}"
                            alt="{{ $property->name }}"
                            class="w-full h-auto object-cover">
                        @else
                        <img src="{{ asset('images/default-image.jpg') }}"
                            alt="Propriété par défaut"
                            class="w-full object-cover">
                        @endif

                        {{-- Contenu de la carte --}}
                        <div class="p-4">
                            {{-- Nom de la propriété --}}
                            <h3 class="text-lg text-gray-800">
                                {{ $property->name ?? 'Nom non disponible' }}
                            </h3>

                            {{-- Localisation --}}
                            <p class="text-gray-700">
                                {{ $property->city ?? 'Ville non disponible' }},
                                {{ Str::words($property->district ?? 'Quartier non disponible') }}
                            </p>

                            {{-- Description tronquée --}}
                            <p class="text-gray-500 mt-5">
                                {{ Str::words($property->description ?? 'Description non disponible', 20, '...') }}
                            </p>

                            {{-- Prix par nuit --}}
                            <p class="text-gray-600 text-right font-bold mt-5">
                                {{ $property->price_per_night ?? 'Prix non disponible' }} € par nuit
                            </p>

                            {{-- Bouton de réservation --}}
                            <div class="mt-4">
                                <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                                    class="border border-blue-500 bg-white-500 text-blue-500 text-center py-2 px-4 rounded block w-full">
                                    Réserver cette résidence
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Boutons de navigation du carrousel --}}
            {{-- Bouton suivant --}}
            <div class="swiper-button-next absolute right-0 top-1/2 transform -translate-y-1/2 z-10">
                <ion-icon class="arrow" name="caret-forward-outline"></ion-icon>
            </div>
            {{-- Bouton précédent --}}
            <div class="swiper-button-prev absolute left-0 top-1/2 transform -translate-y-1/2 z-10">
                <ion-icon class="arrow" name="caret-back-outline"></ion-icon>
            </div>
        </div>
        @endif
    </div>
</div>