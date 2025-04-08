<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Property;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Carbon\Carbon;

class BookingManager extends Component
{
    public $property;
    public $propertyName;
    public $firstImage;
    public $propertyId;
    public $checkInDate;
    public $checkOutDate;
    public $totalPrice;
    public $bookings;
    public $featureIcons = [
        'WiFi' => 'fa-wifi',
        'Piscine' => 'fa-swimming-pool',
        'Parking gratuit' => 'fa-parking',
        'Climatisation' => 'fa-snowflake',
        'TV' => 'fa-tv',
        'Animaux acceptés' => 'fa-paw',
        'Cuisine' => 'fa-utensils',
        'Salle de sport' => 'fa-dumbbell',
        'Jacuzzi' => 'fa-hot-tub',
        'Balcon' => 'fa-balcony',
        'Terrasse' => 'fa-umbrella-beach',
        'Jardin' => 'fa-tree',
        'Barbecue' => 'fa-fire',
        'Lave-linge' => 'fa-washing-machine',
        'Sèche-linge' => 'fa-tshirt',
        'Fer à repasser' => 'fa-iron',
        'Sèche-cheveux' => 'fa-blowdryer',
        'Chauffage' => 'fa-thermometer-half',
        'Coffre-fort' => 'fa-lock',
        'Réveil' => 'fa-clock',
        'Canal+' => 'fa-tv',
        'Netflix' => 'fa-tv',
        'Youtube' => 'fa-brands-youtube',
        'Playstation' => 'fa-gamepad',
        'Eau chaude' => 'fa-water',
        'Groupe électrogène' => 'fa-plug',
        'Petit déjeuné' => 'fa-coffee',
        'Sécurité 24/7' => 'fa-shield-alt',
        'Ascenseur' => 'fa-elevator',
        'Salle de bain privée' => 'fa-bath',
        'Ventilateur' => 'fa-fan',
    ];

    protected $rules = [
        'checkInDate' => 'required|date',
        'checkOutDate' => 'required|date|after:checkInDate',
    ];

    public function mount($propertyId)
    {
        $this->propertyId = $propertyId;
        $this->propertyName = Property::find($propertyId)->name; // Récupère le nom de la propriété par son ID
        $this->checkInDate = Carbon::today()->toDateString(); // Définit la date d'entrée par défaut à aujourd'hui
        $this->property = Property::find($propertyId); // Récupère la propriété par son ID

        if (!$this->property) {
            abort(404, 'Propriété non trouvée'); // Gère le cas où la propriété n'existe pas
        }

        $this->bookings = Booking::where('property_id', $propertyId)->get();
    }

    public function calculateTotalPrice()
    {
        $property = Property::find($this->propertyId);
        $checkIn = strtotime($this->checkInDate);
        $checkOut = strtotime($this->checkOutDate);
        $days = ($checkOut - $checkIn) / 86400; // 86400 seconds in a day
        $this->totalPrice = $days * $property->price_per_night;

        $this->dispatch('show-confirmation', ['totalPrice' => $this->totalPrice]);
        LivewireAlert::title('Le prix total est de ' . $this->totalPrice . '€')->show();
    }

    public function addBooking()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->validate();

        $property = Property::find($this->propertyId);

        // Vérifier si l'utilisateur essaie de réserver sa propre propriété
        if ($property->user_id == Auth::id()) {
            LivewireAlert::title('Vous ne pouvez pas réserver une de vos propriétés')->error()->show();
            return;
        }

        // Vérifier si la date d'entrée est inférieure à la date du jour
        $today = Carbon::today()->toDateString();
        if ($this->checkInDate < $today) {
            LivewireAlert::title('La date d\'entrée ne peut pas être inférieure à la date du jour')->error()->show();
            return;
        }

        Booking::create([
            'property_id' => $this->propertyId,
            'user_id' => Auth::id(), // Assurez-vous que l'utilisateur est authentifié
            'start_date' => $this->checkInDate,
            'end_date' => $this->checkOutDate,
            'total_price' => $this->totalPrice,
        ]);

        $this->bookings = Booking::where('property_id', $this->propertyId)->get();
        LivewireAlert::title('Réservation ajoutée avec succès!')->success()->show();
        // Redirection après l'alerte
        return redirect()->route('home');
    }

    public function render()
    {
        //                return view('livewire.booking-manager')->extends('layouts.app')->section('content');
        $properties = Property::all();
        return view('livewire.booking-manager', compact('properties'));
    }
}
