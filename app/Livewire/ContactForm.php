<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

#[Title('Proposer un hébergement - Afridays')]
class ContactForm extends Component
{
  use WithFileUploads;
  public function mount()
  {
    $user = Auth::user();
    if ($user) {
      $this->prenom = $user->firstname;
      $this->nom = $user->name;
      $this->email = $user->email;
      $this->telephone = $user->telephone ?? ($user->phone ?? '');
    }
  }
  // Informations personnelles
  #[Validate('required|string|max:255')]
  public $nom = '';

  #[Validate('required|string|max:255')]
  public $prenom = '';

  #[Validate('required|email|max:255')]
  public $email = '';

  #[Validate('required|string|max:20')]
  public $telephone = '';

  // Informations de l'établissement
  #[Validate('required|string|max:255')]
  public $nom_etablissement = '';

  #[Validate('required|string')]
  public $type_hebergement = '';


  #[Validate('nullable|string|max:100')]
  public $commune = '';

  #[Validate('nullable|string|max:255')]
  public $plus_details = '';

  #[Validate('required|array|min:5')]
  public $photos = [];


  #[Validate('required|string|max:100')]
  public $ville = '';

  #[Validate('nullable|string|max:100')]
  public $quartier = '';

  #[Validate('required|string')]
  public $adresse = '';

  #[Validate('nullable|numeric')]
  public $latitude = null;

  #[Validate('nullable|numeric')]
  public $longitude = null;

  #[Validate('required|integer|min:1')]
  public $nombre_chambres = 1;

  #[Validate('required|integer|min:1')]
  public $capacite_max = 1;

  #[Validate('required|numeric|min:0')]
  public $prix_nuit = 0;

  // Services et équipements
  public $services = [];
  public $equipements = [];

  #[Validate('required|string|min:50')]
  public $description = '';

  #[Validate('nullable|string')]
  public $message_supplementaire = '';

  public $types_hebergement = [
    'hotel' => 'Hôtel',
    'maison' => 'Maison',
    'appartement' => 'Appartement',
    'villa' => 'Villa',
    'chambre' => 'Chambre d\'hôte',
    'auberge' => 'Auberge',
    'resort' => 'Resort',
    'autre' => 'Autre'
  ];

  public $services_disponibles = [
    'wifi' => 'Wi-Fi gratuit',
    'parking' => 'Parking',
    'piscine' => 'Piscine',
    'restaurant' => 'Restaurant',
    'bar' => 'Bar',
    'salle_sport' => 'Salle de sport',
    'spa' => 'Spa',
    'navette' => 'Navette aéroport',
    'climatisation' => 'Climatisation',
    'petit_dejeuner' => 'Petit-déjeuner inclus',
    'securite' => 'Sécurité 24h/24',
    'menage' => 'Service de ménage',
    'salle_reunion' => 'Salle de réunion',
    'coworking' => 'Espace coworking',
    'vue_mer' => 'Vue sur mer',
    'pmr' => 'Accès PMR (personnes à mobilité réduite)',
    'aire_jeux' => 'Aire de jeux enfants',
    'restaurant_sur_place' => 'Restaurant sur place',
    'bar_lounge' => 'Bar/lounge',
  ];

  public function envoyer()
  {
    $this->validate();

    try {
      // Upload des photos dans public/photos et récupération des URLs
      $photoUrls = [];
      if (is_array($this->photos)) {
        foreach ($this->photos as $photo) {
          if (is_object($photo) && method_exists($photo, 'store')) {
            $path = $photo->store('photos', 'public');
            $photoUrls[] = asset('storage/' . $path);
          }
        }
      }

      // Préparer les données pour l'email
      $donnees = [
        'nom_complet' => $this->prenom . ' ' . $this->nom,
        'email' => $this->email,
        'telephone' => $this->telephone,
        'etablissement' => [
          'nom' => $this->nom_etablissement,
          'type' => $this->types_hebergement[$this->type_hebergement] ?? $this->type_hebergement,
          'adresse' => $this->adresse,
          'ville' => $this->ville,
          'quartier' => $this->quartier,
          'latitude' => $this->latitude,
          'longitude' => $this->longitude,
          'chambres' => $this->nombre_chambres,
          'capacite' => $this->capacite_max,
          'prix' => $this->prix_nuit,
          'services' => array_intersect_key($this->services_disponibles, array_flip($this->services)),
          'description' => $this->description,
          'message' => $this->message_supplementaire,
          'photos' => $photoUrls,
        ]
      ];

      // Envoyer l'email à l'administrateur
      Mail::send('emails.nouvelle-demande-hebergement', $donnees, function ($message) {
        $message->to(config('mail.admin_email', 'admin@afridays.com'))
          ->subject('Nouvelle demande d\'ajout d\'hébergement - Afridays')
          ->from($this->email, $this->prenom . ' ' . $this->nom);
      });

      // Réinitialiser le formulaire
      $this->reset();

      LivewireAlert::title('Demande envoyée avec succès!')
        ->text('Votre demande a été transmise à notre équipe. Nous vous contacterons dans les plus brefs délais.')
        ->success()
        ->show();
    } catch (\Exception $e) {
      LivewireAlert::title('Erreur lors de l\'envoi')
        ->text('Détail : ' . $e->getMessage())
        ->error()
        ->show();
    }
  }

  /**
   * Méthode appelée côté Livewire pour géocoder une adresse
   */
  public function geocodeAddress($address)
  {
    if (empty($address)) return;
    
    try {
      // Utiliser Nominatim pour le géocodage gratuit
      $query = urlencode($address . ', Côte d\'Ivoire');
      $url = "https://nominatim.openstreetmap.org/search?format=json&q={$query}&limit=1";
      
      $context = stream_context_create([
        'http' => [
          'timeout' => 5,
          'user_agent' => 'Afridays-App/1.0'
        ]
      ]);
      
      $response = file_get_contents($url, false, $context);
      
      if ($response !== false) {
        $data = json_decode($response, true);
        
        if (!empty($data) && isset($data[0]['lat'], $data[0]['lon'])) {
          $this->latitude = (float) $data[0]['lat'];
          $this->longitude = (float) $data[0]['lon'];
          
          // Dispatch un événement pour mettre à jour la carte côté client
          $this->dispatch('coordinates-updated', [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
          ]);
        }
      }
    } catch (\Exception $e) {
      // En cas d'erreur, on continue sans géocodage
      \Log::info('Erreur géocodage: ' . $e->getMessage());
    }
  }


  public function render()
  {
    return view('livewire.contact-form');
  }
}
