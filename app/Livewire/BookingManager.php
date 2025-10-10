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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
        // Mémoïsation par requête pour éviter des appels HTTP répétés au sein d'un même cycle
        static $memo = [];
        $start = microtime(true);
        $budget = 7.0; // secondes max pour toute la résolution du taux
        $errors = [];
        $from = strtoupper((string)$from);
        $to = strtoupper((string)$to);
        if (!$from || !$to) {
            return null;
        }
        if ($from === $to) {
            return 1.0;
        }

        $cacheKey = "fx:{$from}:{$to}";
        if (array_key_exists($cacheKey, $memo)) {
            return $memo[$cacheKey];
        }
        // 1) Cache d'abord
        $cached = Cache::get($cacheKey);
        // Si on a un succès en cache
        if (is_numeric($cached) && (float)$cached > 0) {
            $memo[$cacheKey] = (float) $cached;
            return $memo[$cacheKey];
        }
        // Si on a un échec en cache (sentinelle 0)
        if ($cached === 0 || $cached === 'fail') {
            return $memo[$cacheKey] = null;
        }

        // 2) Essai direct via exchangerate.host (gratuit, supporte XOF)
        try {
            $resp = Http::acceptJson()
                ->timeout(4)
                ->connectTimeout(2)
                ->retry(1, 200)
                ->get('https://api.exchangerate.host/convert', [
                    'from' => $from,
                    'to' => $to,
                ]);
            if ($resp->successful()) {
                $data = $resp->json();
                $rate = isset($data['result']) ? (float) $data['result'] : 0.0;
                if ($rate > 0) {
                    Cache::put($cacheKey, $rate, now()->addHours(6));
                    return $memo[$cacheKey] = $rate;
                }
            }
        } catch (\Throwable $e) {
            $errors[] = 'convert: ' . $e->getMessage();
            // on tente un fallback ensuite
        }

        // Stop si on a déjà consommé le budget
        if ((microtime(true) - $start) >= $budget) {
            Cache::put($cacheKey, 0, now()->addMinutes(10));
            return $memo[$cacheKey] = null;
        }

        // 3) Fallback via EUR comme devise pivot (XOF est arrimé à l'EUR à 655.957)
        $EUR_XOF = 655.957; // 1 EUR = 655.957 XOF
        $getEurTo = function (string $symbol) use ($EUR_XOF, $start, $budget) {
            $symbol = strtoupper($symbol);
            if ($symbol === 'EUR') return 1.0;
            if ($symbol === 'XOF') return $EUR_XOF;
            if ((microtime(true) - $start) >= $budget) {
                return 0.0;
            }
            try {
                $r = Http::acceptJson()
                    ->timeout(3)
                    ->connectTimeout(1)
                    // pas de retry pour ne pas dépasser le budget
                    ->get('https://api.exchangerate.host/latest', [
                        'base' => 'EUR',
                        'symbols' => $symbol,
                    ]);
                if ($r->successful()) {
                    $j = $r->json();
                    $val = $j['rates'][$symbol] ?? 0.0;
                    return (float) $val;
                }
            } catch (\Throwable $e) {
                $errors[] = 'latest(EUR→' . $symbol . '): ' . $e->getMessage();
                // ignore
            }
            return 0.0;
        };

        $eurToTo = $getEurTo($to);
        $eurToFrom = $getEurTo($from);
        if ($eurToTo > 0 && $eurToFrom > 0) {
            $rate = $eurToTo / $eurToFrom;
            Cache::put($cacheKey, $rate, now()->addHours(6));
            return $memo[$cacheKey] = $rate;
        }

        // 4) Dernier recours: ancienne API si une clé est fournie
        $apiKey = config('services.exchangerate.key');
        if ((microtime(true) - $start) >= $budget) {
            Cache::put($cacheKey, 0, now()->addMinutes(10));
            return $memo[$cacheKey] = null;
        }
        if ($apiKey && $apiKey !== 'YOUR_API_KEY') {
            $url = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/{$from}/{$to}";
            try {
                $resp = Http::acceptJson()->timeout(3)->connectTimeout(1)->retry(0, 0)->get($url);
                if ($resp->successful()) {
                    $data = $resp->json();
                    $rate = isset($data['conversion_rate']) ? (float) $data['conversion_rate'] : 0.0;
                    if ($rate > 0) {
                        Cache::put($cacheKey, $rate, now()->addHours(6));
                        return $memo[$cacheKey] = $rate;
                    }
                }
            } catch (\Throwable $e) {
                $errors[] = 'v6 pair ' . $from . '→' . $to . ': ' . $e->getMessage();
                // noop
            }

            // Tentative via pivot EUR (utile si la paire directe XOF/* est refusée par l'API)
            if ((microtime(true) - $start) < $budget) {
                $EUR_XOF = 655.957;
                $getEurToV6 = function (string $symbol) use ($apiKey, $EUR_XOF) {
                    $symbol = strtoupper($symbol);
                    if ($symbol === 'EUR') return 1.0;
                    if ($symbol === 'XOF') return $EUR_XOF;
                    try {
                        $u = "https://v6.exchangerate-api.com/v6/{$apiKey}/pair/EUR/{$symbol}";
                        $r = Http::acceptJson()->timeout(3)->connectTimeout(1)->retry(0, 0)->get($u);
                        if ($r->successful()) {
                            $j = $r->json();
                            $v = isset($j['conversion_rate']) ? (float) $j['conversion_rate'] : 0.0;
                            return $v;
                        }
                    } catch (\Throwable $e) {
                        $errors[] = 'v6 pair EUR→' . $symbol . ': ' . $e->getMessage();
                        // ignore
                    }
                    return 0.0;
                };
                $eurToTo = $getEurToV6($to);
                $eurToFrom = $getEurToV6($from);
                if ($eurToTo > 0 && $eurToFrom > 0) {
                    $rate = $eurToTo / $eurToFrom;
                    Cache::put($cacheKey, $rate, now()->addHours(6));
                    return $memo[$cacheKey] = $rate;
                }
            }
        }

        // 4bis) Fallback local: fichier JSON de secours (resources/fx/rates.json)
        try {
            if ((microtime(true) - $start) < $budget) {
                $path = resource_path('fx/rates.json');
                if (is_file($path)) {
                    $json = json_decode(file_get_contents($path), true);
                    $base = strtoupper($json['base'] ?? 'XOF');
                    $table = $json['rates'] ?? [];
                    if ($base === $from && isset($table[$to]) && is_numeric($table[$to])) {
                        $rate = (float) $table[$to];
                        if ($rate > 0) {
                            Cache::put($cacheKey, $rate, now()->addHours(6));
                            Log::info('FX local fallback used', ['pair' => $from . '→' . $to, 'rate' => $rate]);
                            return $memo[$cacheKey] = $rate;
                        }
                    } elseif ($base === 'XOF' && $from !== 'XOF') {
                        // Si base XOF mais on demande p.ex EUR→USD, essayer via XOF pivot
                        $toXof = isset($table[$from]) && (float)$table[$from] > 0 ? (float)$table[$from] : 0.0;
                        $xofToTo = isset($table[$to]) && (float)$table[$to] > 0 ? (float)$table[$to] : 0.0;
                        if ($toXof > 0 && $xofToTo > 0) {
                            $rate = $xofToTo / $toXof;
                            Cache::put($cacheKey, $rate, now()->addHours(6));
                            Log::info('FX local fallback via pivot', ['pair' => $from . '→' . $to, 'rate' => $rate]);
                            return $memo[$cacheKey] = $rate;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            $errors[] = 'local rates.json: ' . $e->getMessage();
        }

        // 5) Échec: mémoriser un échec court et laisser les vues tomber en XOF non converti
        Cache::put($cacheKey, 0, now()->addMinutes(10));
        // Loguer une fois toutes les 10 minutes par paire pour diagnostiquer (réseau / SSL / DNS)
        $logKey = 'fxlog:' . $from . ':' . $to;
        if (!Cache::has($logKey)) {
            Log::warning('FX conversion failed; fallback to XOF', [
                'pair' => $from . '→' . $to,
                'elapsed' => round(microtime(true) - $start, 3) . 's',
                'errors' => $errors,
            ]);
            Cache::put($logKey, 1, now()->addMinutes(10));
        }
        return $memo[$cacheKey] = null;
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

        // Si c'est un hôtel et un type de chambre est sélectionné, on grise toutes les dates
        // couvertes par des réservations de ce type de chambre (accepted OU paid)
        if ($isHotel && $this->selectedRoomTypeId) {
            $roomType = $this->property->roomTypes->firstWhere('id', (int) $this->selectedRoomTypeId);
            if (!$roomType) {
                return [];
            }

            // Récupérer toutes les réservations acceptées OU payées pour ce room type
            $bookings = \App\Models\Booking::query()
                ->where('property_id', $this->property->id)
                ->where('room_type_id', $roomType->id)
                ->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('payment_status', 'paid');
                })
                ->get(['start_date', 'end_date']);

            if ($bookings->isEmpty()) {
                return [];
            }

            // Union de toutes les dates couvertes par ces réservations
            $dates = [];
            foreach ($bookings as $b) {
                $period = new \DatePeriod(
                    new \DateTime($b->start_date),
                    new \DateInterval('P1D'),
                    (new \DateTime($b->end_date))->modify('+1 day')
                );
                foreach ($period as $date) {
                    $dates[] = $date->format('Y-m-d');
                }
            }
            $dates = array_values(array_unique($dates));
            sort($dates);
            return $dates;
        }

        // Sinon: fallback occupation globale de la propriété (résidences meublées etc.)
        $bookings = $this->property->bookings()
            ->where(function ($q) {
                $q->where('status', 'accepted')
                    ->orWhere('payment_status', 'paid');
            })
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

    // Rafraîchir le calendrier quand le type de chambre change
    public function updatedSelectedRoomTypeId($value = null): void
    {
        // Déclenche un événement navigateur pour que le JS relise le JSON et mette à jour Flatpickr
        $this->dispatch('occupied-dates-updated');
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
            // Somme des quantités réservées qui se chevauchent sur la période,
            // soit déjà acceptées, soit déjà payées
            $overlap = Booking::where('property_id', $property->id)
                ->where('room_type_id', $roomType->id)
                ->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('payment_status', 'paid');
                })
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
        // Sinon, résidences meublées: empêcher toute réservation qui chevauche une réservation acceptée ou payée
        if (!$isHotel) {
            $existsOverlap = Booking::where('property_id', $property->id)
                ->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('payment_status', 'paid');
                })
                ->where(function ($q) {
                    $q->whereBetween('start_date', [$this->checkInDate, $this->checkOutDate])
                        ->orWhereBetween('end_date', [$this->checkInDate, $this->checkOutDate])
                        ->orWhere(function ($q2) {
                            $q2->where('start_date', '<=', $this->checkInDate)
                                ->where('end_date', '>=', $this->checkOutDate);
                        });
                })
                ->exists();
            if ($existsOverlap) {
                $this->addError('dateRange', 'Ces dates sont déjà réservées. Veuillez choisir une autre période.');
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
