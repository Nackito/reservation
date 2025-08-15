<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Property;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

#[Title('Home Page - Afridays')]
class HomePage extends Component
{
    public $searchCity = '';
    public $searchMunicipality = '';
    public $showResults = false;
    public $citySuggestions = [];
    public $showCitySuggestions = false;
    public $municipalitySuggestions = [];
    public $showMunicipalitySuggestions = false;

    // Filtres de recherche avancés
    public $propertyType = '';
    public $minPrice = '';
    public $maxPrice = '';
    public $minRooms = '';
    public $maxRooms = '';
    public $selectedAmenities = [];
    public $showFilters = false;

    public $ivorianCities = [
        'Abidjan',
        'Bouaké',
        'Daloa',
        'Yamoussoukro',
        'San-Pédro',
        'Korhogo',
        'Man',
        'Divo',
        'Gagnoa',
        'Anyama',
        'Abengourou',
        'Agboville',
        'Grand-Bassam',
        'Bingerville',
        'Sassandra',
        'Soubré',
        'Issia',
        'Katiola',
        'Tanda',
        'Boundiali',
        'Odienné',
        'Séguéla',
        'Danané',
        'Zuénoula',
        'Duékoué',
        'Bangolo',
        'Guiglo',
        'Bloléquin',
        'Toulepleu',
        'Tabou',
        'Grand-Lahou',
        'Jacqueville',
        'Tiassalé',
        'Adzopé',
        'Alépé',
        'Sikensi',
        'Dabou',
        'Grand-Bereby',
        'Fresco'
    ];
    // Ajout du bouton wishlist (j'aime) sur la page d'accueil
    public function toggleWishlist($propertyId)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $property = Property::find($propertyId);

        if (!$property) {
            session()->flash('error', 'Propriété introuvable');
            return;
        }

        if (!method_exists($user, 'wishlists')) {
            session()->flash('error', 'Relation wishlists manquante sur User');
            return;
        }

        $wishlist = $user->wishlists()->where('property_id', $property->id)->first();
        if ($wishlist) {
            $wishlist->delete();
            session()->flash('message', 'Retiré de votre liste de souhaits');
        } else {
            try {
                $user->wishlists()->create([
                    'property_id' => $property->id,
                ]);
                session()->flash('message', 'Ajouté à votre liste de souhaits !');
            } catch (\Exception $e) {
                session()->flash('error', 'Erreur lors de la modification de la wishlist');
            }
        }
    }

    public function updatedSearchCity()
    {
        if (strlen($this->searchCity) >= 2) {
            $predefinedCities = collect($this->ivorianCities)
                ->filter(function ($city) {
                    return stripos($city, $this->searchCity) !== false;
                });

            $dbCities = Property::select('city')
                ->where('city', 'like', '%' . $this->searchCity . '%')
                ->distinct()
                ->pluck('city');

            $this->citySuggestions = $predefinedCities
                ->merge($dbCities)
                ->unique()
                ->take(5)
                ->values()
                ->toArray();

            $this->showCitySuggestions = !empty($this->citySuggestions);

            // Déclencher automatiquement la recherche
            $this->showResults = true;
        } else {
            $this->showCitySuggestions = false;
            $this->citySuggestions = [];

            // Ne pas changer showResults si clearSearch est en cours
            // Vérifier si tous les critères de recherche sont vides
            if (
                empty($this->searchMunicipality) && empty($this->propertyType) &&
                empty($this->minPrice) && empty($this->maxPrice) &&
                empty($this->minRooms) && empty($this->selectedAmenities)
            ) {
                $this->showResults = false;
            }
        }
    }

    public function updatedSearchMunicipality()
    {
        if (strlen($this->searchMunicipality) >= 2) {
            $this->municipalitySuggestions = Property::select('municipality')
                ->where('municipality', 'like', '%' . $this->searchMunicipality . '%')
                ->whereNotNull('municipality')
                ->distinct()
                ->take(5)
                ->pluck('municipality')
                ->toArray();

            $this->showMunicipalitySuggestions = !empty($this->municipalitySuggestions);

            // Déclencher automatiquement la recherche
            $this->showResults = true;
        } else {
            $this->showMunicipalitySuggestions = false;
            $this->municipalitySuggestions = [];

            // Ne pas changer showResults si clearSearch est en cours
            // Vérifier si tous les critères de recherche sont vides
            if (
                empty($this->searchCity) && empty($this->propertyType) &&
                empty($this->minPrice) && empty($this->maxPrice) &&
                empty($this->minRooms) && empty($this->selectedAmenities)
            ) {
                $this->showResults = false;
            }
        }
    }

    public function selectCity($city)
    {
        Log::info('selectCity called with: ' . $city);
        $this->searchCity = $city;
        $this->showCitySuggestions = false;
        $this->citySuggestions = [];

        if ($this->showResults) {
            $this->search();
        }
    }

    public function selectMunicipality($municipality)
    {
        $this->searchMunicipality = $municipality;
        $this->showMunicipalitySuggestions = false;
        $this->municipalitySuggestions = [];

        if ($this->showResults) {
            $this->search();
        }
    }

    public function search()
    {
        $this->showResults = true;
        $this->showCitySuggestions = false;
        $this->showMunicipalitySuggestions = false;
    }

    public function clearSearch()
    {
        // Désactiver d'abord l'affichage des résultats pour éviter les conflits
        $this->showResults = false;

        // Vider les suggestions en premier
        $this->showCitySuggestions = false;
        $this->citySuggestions = [];
        $this->showMunicipalitySuggestions = false;
        $this->municipalitySuggestions = [];

        // Vider les filtres avant les champs de recherche
        $this->propertyType = '';
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->minRooms = '';
        $this->maxRooms = '';
        $this->selectedAmenities = [];

        // Vider les champs de recherche en dernier
        $this->searchCity = '';
        $this->searchMunicipality = '';

        // Masquer les filtres mobiles si affichés
        $this->showFilters = false;

        // Déclencher l'événement pour réinitialiser les carrousels
        $this->dispatch('refresh-carousels');
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function clearFilters()
    {
        $this->propertyType = '';
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->minRooms = '';
        $this->maxRooms = '';
        $this->selectedAmenities = [];

        // Si aucun autre critère de recherche n'est actif, revenir à l'affichage par défaut
        if (empty($this->searchCity) && empty($this->searchMunicipality)) {
            $this->showResults = false;
        }
    }

    // Méthodes pour déclencher automatiquement la recherche quand les filtres changent
    public function updatedPropertyType()
    {
        $this->showResults = true;
    }

    public function updatedMinPrice()
    {
        $this->showResults = true;
    }

    public function updatedMaxPrice()
    {
        $this->showResults = true;
    }

    public function updatedMinRooms()
    {
        $this->showResults = true;
    }

    public function updatedMaxRooms()
    {
        $this->showResults = true;
    }

    public function updatedSelectedAmenities()
    {
        $this->showResults = true;
    }

    public function searchByCity($city)
    {
        $this->searchCity = $city;
        $this->searchMunicipality = '';
        $this->showResults = true;
        $this->showCitySuggestions = false;
        $this->citySuggestions = [];
        $this->showMunicipalitySuggestions = false;
        $this->municipalitySuggestions = [];
    }

    public function render()
    {
        if ($this->showResults && ($this->searchCity || $this->searchMunicipality || $this->propertyType || $this->minPrice || $this->maxPrice || $this->minRooms || $this->maxRooms || !empty($this->selectedAmenities))) {
            $query = Property::query();

            if ($this->searchCity) {
                $query->where('city', 'like', '%' . $this->searchCity . '%');
            }

            if ($this->searchMunicipality) {
                $query->where('municipality', 'like', '%' . $this->searchMunicipality . '%');
            }

            // Filtres avancés
            if ($this->propertyType) {
                $query->where('property_type', $this->propertyType);
            }

            if ($this->minPrice) {
                $query->where('price_per_night', '>=', $this->minPrice);
            }

            if ($this->maxPrice) {
                $query->where('price_per_night', '<=', $this->maxPrice);
            }

            if ($this->minRooms) {
                $query->where('number_of_rooms', '>=', $this->minRooms);
            }

            if ($this->maxRooms) {
                $query->where('number_of_rooms', '<=', $this->maxRooms);
            }

            // Filtre des commodités
            if (!empty($this->selectedAmenities)) {
                foreach ($this->selectedAmenities as $amenity) {
                    $query->whereJsonContains('features', $amenity);
                }
            }

            $properties = $query->get();
        } else {
            $properties = Property::all();
        }

        // Récupérer les villes populaires avec comptage des propriétés
        $popularCities = Property::select('city')
            ->selectRaw('COUNT(*) as properties_count')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city')
            ->orderBy('properties_count', 'desc')
            ->limit(8)
            ->get();

        // Récupérer les types de propriétés disponibles pour le filtre
        $propertyTypes = Property::select('property_type')
            ->whereNotNull('property_type')
            ->where('property_type', '!=', '')
            ->distinct()
            ->pluck('property_type');

        // Récupérer toutes les commodités disponibles
        $allFeatures = Property::whereNotNull('features')
            ->where('features', '!=', '[]')
            ->pluck('features')
            ->flatten()
            ->unique()
            ->filter()
            ->sort()
            ->values();

        // Récupérer les hébergements les plus visités par ville (basé sur le nombre de réservations)
        $topPropertiesByCity = [];
        $topCities = Property::select('city')
            ->selectRaw('COUNT(*) as properties_count')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->groupBy('city')
            ->orderBy('properties_count', 'desc')
            ->limit(5)
            ->pluck('city');

        foreach ($topCities as $city) {
            $topProperties = Property::where('city', $city)
                ->withCount('bookings')
                ->orderByDesc('bookings_count')
                ->orderByDesc('created_at') // En cas d'égalité, les plus récents d'abord
                ->limit(3)
                ->get();

            if ($topProperties->isNotEmpty()) {
                $topPropertiesByCity[$city] = $topProperties;
            }
        }

        return view('livewire.home-page', [
            'properties' => $properties,
            'popularCities' => $popularCities,
            'propertyTypes' => $propertyTypes,
            'availableAmenities' => $allFeatures,
            'topPropertiesByCity' => $topPropertiesByCity,
        ]);
    }
}
