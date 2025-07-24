<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Property;

#[Title('Home Page - Reservation')]
class HomePage extends Component
{
    public $searchCity = '';
    public $searchMunicipality = '';
    public $showResults = false;
    public $citySuggestions = [];
    public $showCitySuggestions = false;
    public $municipalitySuggestions = [];
    public $showMunicipalitySuggestions = false;

    // Liste des principales villes de Côte d'Ivoire
    public $ivorianCities = [
        'Abidjan', 'Bouaké', 'Daloa', 'Yamoussoukro', 'San-Pédro', 'Korhogo',
        'Man', 'Divo', 'Gagnoa', 'Anyama', 'Abengourou', 'Agboville',
        'Grand-Bassam', 'Bingerville', 'Sassandra', 'Soubré', 'Issia',
        'Katiola', 'Tanda', 'Boundiali', 'Odienné', 'Séguéla', 'Danané',
        'Zuénoula', 'Duékoué', 'Bangolo', 'Guiglo', 'Bloléquin', 'Toulepleu',
        'Tabou', 'Grand-Lahou', 'Jacqueville', 'Tiassalé', 'Adzopé',
        'Alépé', 'Sikensi', 'Dabou', 'Grand-Bereby', 'Fresco'
    ];

    public function updatedSearchCity()
    {
        if (strlen($this->searchCity) >= 2) {
            // Recherche dans les villes prédéfinies ET dans la base de données
            $predefinedCities = collect($this->ivorianCities)
                ->filter(function ($city) {
                    return stripos($city, $this->searchCity) !== false;
                });

            // Recherche dans les villes de la base de données
            $dbCities = Property::select('city')
                ->where('city', 'like', '%' . $this->searchCity . '%')
                ->distinct()
                ->pluck('city');

            // Combinaison et limitation à 5 résultats
            $this->citySuggestions = $predefinedCities
                ->merge($dbCities)
                ->unique()
                ->take(5)
                ->values()
                ->toArray();
            
            $this->showCitySuggestions = !empty($this->citySuggestions);
        } else {
            $this->showCitySuggestions = false;
            $this->citySuggestions = [];
        }
    }

    public function updatedSearchMunicipality()
    {
        if (strlen($this->searchMunicipality) >= 2) {
            // Recherche dans les quartiers de la base de données
            $this->municipalitySuggestions = Property::select('municipality')
                ->where('municipality', 'like', '%' . $this->searchMunicipality . '%')
                ->whereNotNull('municipality')
                ->distinct()
                ->take(5)
                ->pluck('municipality')
                ->toArray();
            
            $this->showMunicipalitySuggestions = !empty($this->municipalitySuggestions);
        } else {
            $this->showMunicipalitySuggestions = false;
            $this->municipalitySuggestions = [];
        }
    }

    public function selectMunicipality($municipality)
    {
        $this->searchMunicipality = $municipality;
        $this->showMunicipalitySuggestions = false;
        $this->municipalitySuggestions = [];
    }

    public function selectCity($city)
    {
        $this->searchCity = $city;
        $this->showCitySuggestions = false;
        $this->citySuggestions = [];
    }

    public function search()
    {
        $this->showResults = true;
        $this->showCitySuggestions = false;
        $this->showMunicipalitySuggestions = false;
    }

    public function clearSearch()
    {
        $this->searchCity = '';
        $this->searchMunicipality = '';
        $this->showResults = false;
        $this->showCitySuggestions = false;
        $this->citySuggestions = [];
        $this->showMunicipalitySuggestions = false;
        $this->municipalitySuggestions = [];
    }

    public function render()
    {
        if ($this->showResults && ($this->searchCity || $this->searchMunicipality)) {
            $query = Property::query();
            
            if ($this->searchCity) {
                $query->where('city', 'like', '%' . $this->searchCity . '%');
            }
            
            if ($this->searchMunicipality) {
                $query->where('municipality', 'like', '%' . $this->searchMunicipality . '%');
            }
            
            $properties = $query->get();
        } else {
            $properties = Property::all();
        }

        return view('livewire.home-page', [
            'properties' => $properties,
        ]);
    }
}
