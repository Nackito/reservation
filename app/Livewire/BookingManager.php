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
use App\Events\MessageSent;

/**
 * Récupère le taux de change depuis une API externe (exchangerate-api.com)
 * @param string $from Devise source (ex: 'XOF')
 * @param string $to Devise cible (ex: 'EUR')
 * @return float|null
 */

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
    public $eligibleBookingId; // booking éligible pour laisser un avis
    public $userHasReview = false; // l'utilisateur a déjà un avis pour cette propriété
    public $avgRating; // moyenne des avis approuvés
    public $approvedReviewsCount; // nombre d'avis approuvés
    // Gestion des types de chambre pour les hôtels
    public $selectedRoomTypeId = null;
    public $quantity = 1;
    // Icônes normalisées par clé (minuscules, sans accents, sans espaces/ponctuation)
    public $featureIcons = [
        // Nouvelles clés (Filament)
        'wifi' => 'fa-wifi',
        'parking' => 'fa-parking',
        'clim' => 'fa-snowflake',
        'piscine' => 'fa-swimming-pool',
        'jardin' => 'fa-tree',
        'balcon' => 'fa-building',
        'ascenseur' => 'fa-elevator',
        'meuble' => 'fa-couch',
        'terrasse' => 'fa-umbrella-beach',

        // Anciennes valeurs textuelles (compat)
        'wifiold' => 'fa-wifi', // alias placeholder si besoin
        'wifigratuit' => 'fa-wifi',
        'parkinggratuit' => 'fa-parking',
        'climatisation' => 'fa-snowflake',
        'tv' => 'fa-tv',
        'animauxacceptes' => 'fa-paw',
        'cuisine' => 'fa-utensils',
        'salledesport' => 'fa-dumbbell',
        'jacuzzi' => 'fa-hot-tub',
        'barbecue' => 'fa-fire',
        'lavelinge' => 'fa-tshirt', // substitution +/-
        'sechelange' => 'fa-tshirt',
        'sechel30' => 'fa-tshirt',
        'sechelinge' => 'fa-tshirt',
        'sechecheveux' => 'fa-bath', // fallback plus standard
        'ferarepasser' => 'fa-shirt', // fallback
        'chauffage' => 'fa-thermometer-half',
        'coffrefort' => 'fa-lock',
        'reveil' => 'fa-clock',
        'canal' => 'fa-tv',
        'netflix' => 'fa-tv',
    ];

    private function normalizeFeatureKey($value): string
    {
        $v = \Illuminate\Support\Str::of((string) $value)
            ->lower()
            ->ascii()
            ->replace([" ", "-", "_", "'", "+", "."], '')
            ->value();
        return $v;
    }

    public function iconClassForFeature($feature): string
    {
        $norm = $this->normalizeFeatureKey($feature);
        // clés exactes (nouvelles) ou compat anciennes
        if (isset($this->featureIcons[$norm])) {
            return $this->featureIcons[$norm];
        }
        // Tentative fallback: retirer mots courts
        $simpler = preg_replace('/(gratuit|free)$/', '', $norm);
        if ($simpler && isset($this->featureIcons[$simpler])) {
            return $this->featureIcons[$simpler];
        }
        // Encore un fallback: si l'ancienne clé exacte existe
        if (isset($this->featureIcons[$feature])) {
            return $this->featureIcons[$feature];
        }
        return 'fa-circle';
    }
    public function getExchangeRate($from, $to)
    {
        if ($from === $to) return 1.0;
        // Remplacez par votre clé API réelle
        $apiKey = config('services.exchangerate.key', 'YOUR_API_KEY');
        $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$from}/{$to}";
        try {
            $response = @file_get_contents($url);
            if ($response === false) return null;
            $data = json_decode($response, true);
            if (isset($data['conversion_rate'])) {
                return (float) $data['conversion_rate'];
            }
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
        }
        return null;
    }

    /**
     * Retourne le prix converti selon la devise de l'utilisateur connecté
     * @param float $amount Montant en XOF (devise de base)
     * @return array ['amount' => float, 'currency' => string]
     */
    public function getConvertedPrice($amount)
    {
        $user = Auth::user();
        $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
        if ($userCurrency === 'XOF') {
            return ['amount' => $amount, 'currency' => 'XOF'];
        }
        $rate = $this->getExchangeRate('XOF', $userCurrency);
        if ($rate) {
            return [
                'amount' => round($amount * $rate, 2),
                'currency' => $userCurrency
            ];
        }
        // Fallback : retourne le montant d'origine
        return ['amount' => $amount, 'currency' => 'XOF'];
    }

    // The following code should be moved to a lifecycle method such as mount() or a custom method.
    // Example: Move initialization logic to mount()

    public function mount()
    {
        // Recupérer la propriété
        $this->property = Property::with(['images', 'category', 'roomTypes'])->find($this->propertyId);

        $this->dateRange = null;
        $this->checkInDate = null;
        $this->checkOutDate = null;

        // Convertir la description Markdown en HTML
        if ($this->property && $this->property->description) {
            $this->property->description = Str::markdown($this->property->description);
        }

        if (isset($this->propertyId)) {
            $this->bookings = Booking::where('property_id', $this->propertyId)->get();

            // Pré-sélection d'un type de chambre pour les hôtels (si disponible)
            if ($this->property && $this->property->category && ($this->property->category->name === 'Hôtel' || $this->property->category->name === 'Hotel')) {
                $firstType = $this->property->roomTypes->first();
                $this->selectedRoomTypeId = $firstType?->id;
            }

            // Récupérer les avis approuvés liés à cette propriété
            $this->reviews = Reviews::where('property_id', $this->propertyId)
                ->where('approved', true) // Filtrer les avis approuvés
                ->with('user') // Charger les utilisateurs qui ont laissé des avis
                ->get();

            $this->approvedReviewsCount = $this->reviews ? $this->reviews->count() : 0;
            $this->avgRating = ($this->approvedReviewsCount > 0)
                ? round((float) $this->reviews->avg('rating'), 1)
                : null;

            // Déterminer si l'utilisateur peut laisser un avis (séjour terminé pour ce bien)
            if (Auth::check()) {
                $eligibleBooking = Booking::where('property_id', $this->propertyId)
                    ->where('user_id', Auth::id())
                    ->where('status', 'accepted')
                    ->whereDate('end_date', '<', now())
                    ->orderByDesc('end_date')
                    ->first();

                $this->eligibleBookingId = $eligibleBooking?->id;
                $this->canLeaveReview = (bool) $eligibleBooking;

                // L'utilisateur a-t-il déjà un avis pour ce bien ?
                $this->userHasReview = Reviews::where('user_id', Auth::id())
                    ->where('property_id', $this->propertyId)
                    ->exists();
            }
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

        $isHotel = $this->property->category && in_array($this->property->category->name, ['Hôtel', 'Hotel']);

        // Si c'est un hôtel et un type de chambre est sélectionné, on calcule les dates où ce type est saturé
        if ($isHotel && $this->selectedRoomTypeId) {
            $roomType = $this->property->roomTypes->firstWhere('id', (int) $this->selectedRoomTypeId);
            if (!$roomType) {
                return [];
            }

            $inventory = max(0, (int) $roomType->inventory);
            if ($inventory <= 0) {
                // Aucun stock: toutes les dates deviennent grisées? On retourne vide pour ne pas bloquer toute l'année.
                return [];
            }

            // Récupérer toutes les réservations acceptées pour ce room type
            $bookings = \App\Models\Booking::query()
                ->where('property_id', $this->property->id)
                ->where('room_type_id', $roomType->id)
                ->where('status', 'accepted')
                ->get(['start_date', 'end_date', 'quantity']);

            if ($bookings->isEmpty()) {
                return [];
            }

            // Construire un compteur par jour: somme des quantities par date
            $counts = [];
            foreach ($bookings as $b) {
                $period = new \DatePeriod(
                    new \DateTime($b->start_date),
                    new \DateInterval('P1D'),
                    (new \DateTime($b->end_date))->modify('+1 day')
                );
                foreach ($period as $date) {
                    $key = $date->format('Y-m-d');
                    $counts[$key] = ($counts[$key] ?? 0) + (int) $b->quantity;
                }
            }

            // Dates saturées = somme >= inventaire
            $occupied = [];
            foreach ($counts as $d => $sum) {
                if ($sum >= $inventory) {
                    $occupied[] = $d;
                }
            }
            sort($occupied);
            return $occupied;
        }

        // Sinon: fallback occupation globale de la propriété (résidences meublées etc.)
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
        $property = $this->property ?? Property::with('roomTypes')->find($this->propertyId);
        $checkIn = strtotime($this->checkInDate);
        $checkOut = strtotime($this->checkOutDate);
        $days = ($checkOut - $checkIn) / 86400; // 86400 seconds in a day
        if ($days < 1) {
            $days = 1;
        }
        // Déterminer le prix unitaire selon le type de chambre sélectionné
        $unitPrice = $property->price_per_night;
        if ($this->selectedRoomTypeId) {
            $rt = $property->roomTypes->firstWhere('id', (int) $this->selectedRoomTypeId);
            if ($rt && $rt->price_per_night !== null) {
                $unitPrice = (float) $rt->price_per_night;
            }
        }
        $qty = max(1, (int) $this->quantity);
        $this->totalPrice = $days * $unitPrice * $qty;
        // Conversion pour affichage (facultatif, à utiliser dans la vue)
        $converted = $this->getConvertedPrice($this->totalPrice);
        $this->convertedPrice = $converted['amount'];
        $this->convertedCurrency = $converted['currency'];
    }
    // Ajoute ces propriétés publiques pour l'affichage dans la vue
    public $convertedPrice;
    public $convertedCurrency;


    public function addBooking()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $rules = $this->rules;
        // Si hôtel, type de chambre requis et quantité valide
        $isHotel = $this->property && $this->property->category && ($this->property->category->name === 'Hôtel' || $this->property->category->name === 'Hotel');
        if ($isHotel) {
            $rules['selectedRoomTypeId'] = 'required|integer|exists:room_types,id';
            $rules['quantity'] = 'required|integer|min:1';
        }
        $this->validate($rules);

        // Découper la plage de dates (tous séparateurs courants FR/EN, nettoyage des espaces insécables)
        if ($this->dateRange) {
            $dates = preg_split('/\\s+(?:to|à|au|\\-|–|—)\\s+/ui', $this->dateRange);
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

        $property = $this->property ?? Property::with('roomTypes')->find($this->propertyId);

        // Calculer et stocker le prix total avant la création de la réservation
        // (sinon $this->totalPrice reste null et ne s'enregistre pas)
        $this->calculateTotalPrice();

        // Vérifier l'inventaire/ disponibilité pour le type de chambre (si hôtel)
        if ($isHotel && $this->selectedRoomTypeId) {
            $roomType = $property->roomTypes->firstWhere('id', (int) $this->selectedRoomTypeId);
            if (!$roomType) {
                $this->addError('selectedRoomTypeId', 'Type de chambre introuvable.');
                return;
            }
            // Somme des quantités réservées qui se chevauchent sur la période, statut accepté
            $overlap = Booking::where('property_id', $property->id)
                ->where('room_type_id', $roomType->id)
                ->where('status', 'accepted')
                ->where(function ($q) {
                    $q->whereBetween('start_date', [$this->checkInDate, $this->checkOutDate])
                        ->orWhereBetween('end_date', [$this->checkInDate, $this->checkOutDate])
                        ->orWhere(function ($q2) {
                            $q2->where('start_date', '<=', $this->checkInDate)
                                ->where('end_date', '>=', $this->checkOutDate);
                        });
                })
                ->sum('quantity');
            $requested = max(1, (int) $this->quantity);
            if (($overlap + $requested) > (int) $roomType->inventory) {
                $this->addError('quantity', 'La quantité demandée dépasse la disponibilité pour ces dates.');
                return;
            }
        }

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
            'room_type_id' => $this->selectedRoomTypeId,
            'quantity' => max(1, (int) $this->quantity),
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
        $userMessage = Message::create([
            'conversation_id' => $adminGroupConversation->id,
            'sender_id' => Auth::id(),
            'receiver_id' => 5, // ID d'un admin pour la contrainte SQL
            'content' => 'Bonjour, je suis Mr/Mme ' . $userName . ', je souhaite réserver ' . ($property ? $property->name : '') . ' du ' . $this->checkInDate . ' au ' . $this->checkOutDate . '. Merci de confirmer la disponibilité.',
        ]);

        // Diffuser le message initial pour mise à jour temps réel côté admin
        try {
            broadcast(new MessageSent($userMessage));
        } catch (\Throwable $e) {
            // Silencieux en cas d'absence de broadcasting configuré
        }

        // Réponse automatique de l'admin dans le même canal (anti-doublon simple)
        try {
            $alreadyAutoReplied = Message::where('conversation_id', $adminGroupConversation->id)
                ->where('sender_id', 5)
                ->where('created_at', '>=', now()->subMinutes(10))
                ->exists();

            if (!$alreadyAutoReplied) {
                $firstName = trim(Str::of($userName)->before(' '));
                $autoContent = 'Bonjour ' . ($firstName !== '' ? $firstName : $userName) . ", votre demande de réservation a bien été reçue, nous vérifions la disponibilité et reviendrons vers vous dans un instant.";

                $autoMessage = Message::create([
                    'conversation_id' => $adminGroupConversation->id,
                    'sender_id' => 5,
                    'receiver_id' => Auth::id(),
                    'content' => $autoContent,
                ]);

                // Diffuser la réponse automatique pour l'utilisateur
                try {
                    broadcast(new MessageSent($autoMessage));
                } catch (\Throwable $e) {
                    // Ignorer si broadcasting non configuré
                }
            }
        } catch (\Throwable $e) {
            // Ignorer discrètement si problème lors de l'auto-réponse
        }

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
