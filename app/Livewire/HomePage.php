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
    // Appliquer les filtres depuis le bouton mobile
    public function applyFilters()
    {
        $this->showResults = true;
        $this->showFilters = false;
        $this->dispatch('refresh-carousels');
    }
    public $searchQuery = '';
    public $showResults = false;
    public $suggestions = [];
    public $showSuggestions = false;

    // Filtres de recherche avancés
    public $propertyType = '';
    public $minPrice = '';
    public $maxPrice = '';
    public $minRooms = '';
    public $maxRooms = '';
    public $selectedAmenities = [];
    public $showFilters = false;
    public $showCitySuggestions = false;
    public $showMunicipalitySuggestions = false;

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
        // Réinitialise le carrousel après modification de la wishlist
        $this->dispatch('refresh-carousels');
    }

    public function updatedSearchQuery()
    {
        if (strlen($this->searchQuery) >= 2) {
            $predefined = collect($this->ivorianCities)
                ->filter(function ($city) {
                    return stripos($city, $this->searchQuery) !== false;
                });

            $dbCities = Property::select('city')
                ->where('city', 'like', '%' . $this->searchQuery . '%')
                ->distinct()
                ->pluck('city');

            $dbMunicipalities = Property::select('municipality')
                ->where('municipality', 'like', '%' . $this->searchQuery . '%')
                ->whereNotNull('municipality')
                ->distinct()
                ->pluck('municipality');

            $dbDistricts = Property::select('district')
                ->where('district', 'like', '%' . $this->searchQuery . '%')
                ->whereNotNull('district')
                ->distinct()
                ->pluck('district');

            $this->suggestions = $predefined
                ->merge($dbCities)
                ->merge($dbMunicipalities)
                ->merge($dbDistricts)
                ->unique()
                ->take(5)
                ->values()
                ->toArray();

            $this->showSuggestions = !empty($this->suggestions);
            $this->showResults = true;
        } else {
            $this->showSuggestions = false;
            $this->suggestions = [];
            if (
                empty($this->propertyType) &&
                empty($this->minPrice) && empty($this->maxPrice) &&
                empty($this->minRooms) && empty($this->selectedAmenities)
            ) {
                $this->showResults = false;
            }
        }
    }

    // supprimé : fusionné dans updatedSearchQuery()

    public function selectSuggestion($value)
    {
        $this->searchQuery = $value;
        $this->showSuggestions = false;
        $this->suggestions = [];
        if ($this->showResults) {
            $this->search();
        }
    }

    // supprimé : fusionné dans selectSuggestion()

    public function search()
    {
        $this->showResults = true;
        $this->showCitySuggestions = false;
        $this->showMunicipalitySuggestions = false;
        $this->dispatch('refresh-carousels');
    }

    public function clearSearch()
    {
        // Désactiver d'abord l'affichage des résultats pour éviter les conflits
        $this->showResults = false;

        // Vider les suggestions en premier
        $this->showSuggestions = false;
        $this->suggestions = [];
        $this->dispatch('refresh-carousels');

        // Vider les filtres avant les champs de recherche
        $this->propertyType = '';
        $this->minPrice = '';
        $this->maxPrice = '';
        $this->minRooms = '';
        $this->maxRooms = '';
        $this->selectedAmenities = [];
        $this->dispatch('refresh-carousels');

        // Vider les champs de recherche en dernier
        $this->searchQuery = '';
        $this->dispatch('refresh-carousels');

        // Masquer les filtres mobiles si affichés
        $this->showFilters = false;
        $this->dispatch('refresh-carousels');

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
        $this->dispatch('refresh-carousels');

        // Si aucun autre critère de recherche n'est actif, revenir à l'affichage par défaut
        if (empty($this->searchQuery)) {
            $this->showResults = false;
        }
        $this->dispatch('refresh-carousels');
    }

    // Méthodes pour déclencher automatiquement la recherche quand les filtres changent
    public function updatedPropertyType()
    {
        $this->showResults = true;
        $this->dispatch('refresh-carousels');
    }

    public function updatedMinPrice()
    {
        $this->showResults = true;
        $this->dispatch('refresh-carousels');
    }

    public function updatedMaxPrice()
    {
        $this->showResults = true;
        $this->dispatch('refresh-carousels');
    }

    public function updatedMinRooms()
    {
        $this->showResults = true;
        $this->dispatch('refresh-carousels');
    }

    public function updatedMaxRooms()
    {
        $this->showResults = true;
        $this->dispatch('refresh-carousels');
    }

    public function updatedSelectedAmenities()
    {
        $this->showResults = true;
        $this->dispatch('refresh-carousels');
    }

    public function searchByCity($city)
    {
        $this->searchQuery = $city;
        $this->showResults = true;
        $this->showSuggestions = false;
        $this->suggestions = [];
        $this->dispatch('refresh-carousels');
    }

    public function render()
    {
        if ($this->showResults && ($this->searchQuery || $this->propertyType || $this->minPrice || $this->maxPrice || $this->minRooms || $this->maxRooms || !empty($this->selectedAmenities))) {
            $query = Property::query();

            if ($this->searchQuery) {
                $raw = trim($this->searchQuery);
                // Tokeniser par espaces/virgules/points-virgules
                $tokens = preg_split('/[\s,;]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $tokens = array_values(array_filter(array_map(fn($t) => mb_strtolower(trim($t)), $tokens)));

                if (!empty($tokens)) {
                    // Chaque mot doit correspondre à l'une des colonnes (AND sur mots, OR sur colonnes)
                    $query->where(function ($outer) use ($tokens) {
                        foreach ($tokens as $tok) {
                            $outer->where(function ($q) use ($tok) {
                                $noSpace = str_replace(' ', '', $tok);
                                // LIKE insensible à la casse (selon collation) + variantes sans espaces
                                $q->whereRaw('LOWER(COALESCE(city, "")) LIKE ?', ['%' . $tok . '%'])
                                    ->orWhereRaw('LOWER(COALESCE(municipality, "")) LIKE ?', ['%' . $tok . '%'])
                                    ->orWhereRaw('LOWER(COALESCE(district, "")) LIKE ?', ['%' . $tok . '%'])
                                    ->orWhereRaw('REPLACE(LOWER(COALESCE(city, "")), " ", "") LIKE ?', ['%' . $noSpace . '%'])
                                    ->orWhereRaw('REPLACE(LOWER(COALESCE(municipality, "")), " ", "") LIKE ?', ['%' . $noSpace . '%'])
                                    ->orWhereRaw('REPLACE(LOWER(COALESCE(district, "")), " ", "") LIKE ?', ['%' . $noSpace . '%']);

                                // Correspondance approchée: SOUNDEX (MySQL)
                                if (mb_strlen($tok) >= 4) {
                                    $q->orWhereRaw('SOUNDEX(COALESCE(city, "")) = SOUNDEX(?)', [$tok])
                                        ->orWhereRaw('SOUNDEX(COALESCE(municipality, "")) = SOUNDEX(?)', [$tok])
                                        ->orWhereRaw('SOUNDEX(COALESCE(district, "")) = SOUNDEX(?)', [$tok]);
                                }
                            });
                        }
                    });
                }
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

        // Pour chaque ville populaire, récupérer une image aléatoire
        foreach ($popularCities as $cityRow) {
            $imageUrl = asset('images/default-image.jpg');

            $topProperty = Property::where('city', $cityRow->city)
                ->withCount('bookings')
                ->orderByDesc('bookings_count')
                ->orderByDesc('created_at')
                ->first();

            if ($topProperty) {
                $randomImage = $topProperty->images()->inRandomOrder()->first();
                if ($randomImage && !empty($randomImage->image_path)) {
                    $imageUrl = asset('storage/' . $randomImage->image_path);
                } else {
                    $first = $topProperty->firstImage();
                    if ($first && !empty($first->image_path)) {
                        $imageUrl = asset('storage/' . $first->image_path);
                    }
                }
            }

            // Attribut dynamique accessible dans la vue
            $cityRow->city_image_url = $imageUrl;
        }

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
