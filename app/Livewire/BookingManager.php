<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Property;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Reviews;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;
use Carbon\Carbon;

class BookingManager extends Component
{
    // Règles de validation Livewire
    public $rules = [
        'dateRange' => 'required|string',
    ];
    public $property;
    public $propertyName;
    public $firstImage;
    public $propertyId;
    public $dateRange;
    public $checkInDate;
    public $checkOutDate;
    public $totalPrice;
    public $bookings;
    public $reviews;
    public $rating;
    public $canLeaveReview = false;
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
    ];

    // The following code should be moved to a lifecycle method such as mount() or a custom method.
    // Example: Move initialization logic to mount()

    public function mount()
    {
        // Recupérer la propriété
        $this->property = Property::find($this->propertyId);

        $this->dateRange = null;
        $this->checkInDate = null;
        $this->checkOutDate = null;

        // Convertir la description Markdown en HTML
        if ($this->property && $this->property->description) {
            $this->property->description = Str::markdown($this->property->description);
        }

        if (isset($this->propertyId)) {
            $this->bookings = Booking::where('property_id', $this->propertyId)->get();

            // Récupérer les avis approuvés liés à cette propriété
            $this->reviews = Reviews::where('property_id', $this->propertyId)
                ->where('approved', true) // Filtrer les avis approuvés
                ->with('user') // Charger les utilisateurs qui ont laissé des avis
                ->get();
        }
    }

    public function submitReview()
    {
        $this->validate([
            'review' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        Reviews::create([
            'user_id' => Auth::id(),
            'property_id' => $this->propertyId,
            'review' => $this->review,
            'rating' => $this->rating,
            'approved' => false, // En attente de validation par l'admin
        ]);

        session()->flash('message', 'Votre avis a été soumis et est en attente de validation.');
        $this->reset(['review', 'rating']);
    }

    /**
     * Retourne la liste des dates occupées (format YYYY-MM-DD) pour Flatpickr
     */
    public function getOccupiedDatesProperty()
    {
        if (!$this->property) {
            return [];
        }
        $bookings = $this->property->bookings()
            ->where('status', 'accepted')
            ->get(['start_date', 'end_date']);
        $dates = [];
        foreach ($bookings as $booking) {
            $period = new \DatePeriod(
                new \DateTime($booking->start_date),
                new \DateInterval('P1D'),
                (new \DateTime($booking->end_date))->modify('+1 day')
            );
            foreach ($period as $date) {
                $dates[] = $date->format('Y-m-d');
            }
        }
        return array_values(array_unique($dates));
    }

    public function calculateTotalPrice()
    {
        $property = Property::find($this->propertyId);
        $checkIn = strtotime($this->checkInDate);
        $checkOut = strtotime($this->checkOutDate);
        $days = ($checkOut - $checkIn) / 86400; // 86400 seconds in a day
        $this->totalPrice = $days * $property->price_per_night;
        // Plus d'alerte ni d'affichage du prix ici
    }


    public function addBooking()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->validate();

        // Découper la plage de dates (format Flatpickr : "YYYY-MM-DD to YYYY-MM-DD" ou "YYYY-MM-DD à YYYY-MM-DD" ou "YYYY-MM-DD - YYYY-MM-DD")
        if ($this->dateRange) {
            $dates = preg_split('/\s*(to|à|-)\s*/', $this->dateRange);
            if (count($dates) === 2) {
                $this->checkInDate = trim($dates[0]);
                $this->checkOutDate = trim($dates[1]);
            } else {
                $this->addError('dateRange', 'Format de plage de dates invalide.');
                return;
            }
        } else {
            $this->addError('dateRange', 'Veuillez sélectionner une plage de dates.');
            return;
        }

        // Validation supplémentaire : checkIn < checkOut
        if (!$this->checkInDate || !$this->checkOutDate || $this->checkInDate >= $this->checkOutDate) {
            $this->addError('dateRange', 'La date de départ doit être postérieure à la date d\'arrivée.');
            return;
        }

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

        $booking = Booking::create([
            'property_id' => $this->propertyId,
            'user_id' => Auth::id(),
            'start_date' => $this->checkInDate,
            'end_date' => $this->checkOutDate,
            'total_price' => $this->totalPrice,
            'status' => 'pending', // Statut en attente pour Filament/Admin
        ]);

        // Canal admin groupé : conversation unique pour tous les admins
        $property = Property::find($this->propertyId);
        // Créer un canal admin groupé unique pour chaque réservation
        $adminGroupConversation = \App\Models\Conversation::create([
            'is_admin_channel' => true,
            'user_id' => Auth::id(),
            'owner_id' => 5, // ou null si pas utile
            'booking_id' => $booking->id
        ]);

        $userName = Auth::user()->name ?? 'Utilisateur';
        Message::create([
            'conversation_id' => $adminGroupConversation->id,
            'sender_id' => Auth::id(),
            'receiver_id' => 5, // ID d'un admin pour la contrainte SQL
            'content' => 'Bonjour, je suis Mr/Mme ' . $userName . ', je souhaite réserver ' . ($property ? $property->name : '') . ' du ' . $this->checkInDate . ' au ' . $this->checkOutDate . '. Merci de confirmer la disponibilité.',
        ]);

        // Envoi d'un mail à l'utilisateur
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Votre demande de reservation à bien été soumise, nous vérifions la disponibilité...",
                function ($message) {
                    $message->to(Auth::user()->email)
                        ->subject('Demande de réservation soumise');
                }
            );
        } catch (\Exception $e) {
            // Optionnel : log ou alerte
        }

        // Envoi d'un mail à l'admin (id 5 ou tous les admins si besoin)
        try {
            $admin = User::find(5); // Adapter si plusieurs admins
            if ($admin) {
                \Illuminate\Support\Facades\Mail::raw(
                    "Vous avez une demande de reservation en attente.",
                    function ($message) use ($admin) {
                        $message->to($admin->email)
                            ->subject('Nouvelle demande de réservation');
                    }
                );
                // Notification Laravel (canal database)
                $admin->notify(new \App\Notifications\BookingRequestNotification($booking));
            }
        } catch (\Exception $e) {
            // Optionnel : log ou alerte
        }

        $this->bookings = Booking::where('property_id', $this->propertyId)->get();
        // Redirection directe vers la page de chat générale
        return redirect('/chat');
    }

    public function toggleWishlist()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $property = Property::find($this->propertyId);

        if (!$property) {
            LivewireAlert::title('Propriété introuvable')->error()->show();
            return;
        }

        if (!method_exists($user, 'wishlists')) {
            LivewireAlert::title('Relation wishlists manquante sur User')->error()->show();
            return;
        }

        $wishlist = $user->wishlists()->where('property_id', $property->id)->first();
        if ($wishlist) {
            // Retirer de la wishlist
            $wishlist->delete();
            LivewireAlert::title('Retiré de votre liste de souhaits')->info()->show();
        } else {
            // Ajouter à la wishlist
            try {
                $user->wishlists()->create([
                    'property_id' => $property->id,
                ]);
                LivewireAlert::title('Ajouté à votre liste de souhaits !')->success()->show();
            } catch (\Exception $e) {
                LivewireAlert::title('Erreur lors de la modification de la wishlist')->error()->show();
            }
        }
    }

    public function render()
    {
        $properties = Property::all();
        $occupiedDates = $this->occupiedDates;
        return view('livewire.booking-manager', compact('properties', 'occupiedDates'));
    }
}
