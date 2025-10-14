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

        // Anciennes valeurs textuelles (compat)
        'wifiold' => 'fa-wifi',
        'wifigratuit' => 'fa-wifi',
        'parkinggratuit' => 'fa-parking',
        'climatisation' => 'fa-snowflake',
        'tv' => 'fa-tv',
        'animauxacceptes' => 'fa-paw',
        'cuisine' => 'fa-utensils',
        'salledesport' => 'fa-dumbbell',
        'jacuzzi' => 'fa-hot-tub',
        'barbecue' => 'fa-fire',
        'lavelinge' => 'fa-tshirt',
        'sechelange' => 'fa-tshirt',
        'sechel30' => 'fa-tshirt',
        'sechelinge' => 'fa-tshirt',
        'sechecheveux' => 'fa-bath',
        'ferarepasser' => 'fa-shirt',
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
        if (isset($this->featureIcons[$norm])) {
            return $this->featureIcons[$norm];
        }
        $simpler = preg_replace('/(gratuit|free)$/', '', $norm);
        if ($simpler && isset($this->featureIcons[$simpler])) {
            return $this->featureIcons[$simpler];
        }
        if (isset($this->featureIcons[$feature])) {
            return $this->featureIcons[$feature];
        }
        return 'fa-circle';
    }

    public function getExchangeRate($from, $to)
    {
        // Mémoïsation par requête
        static $memo = [];
        $start = microtime(true);
        $budget = 7.0; // secondes max
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
        if (is_numeric($cached) && (float)$cached > 0) {
            $memo[$cacheKey] = (float) $cached;
            return $memo[$cacheKey];
        }
        if ($cached === 0 || $cached === 'fail') {
            return $memo[$cacheKey] = null;
        }

        // 2) Essai direct via exchangerate.host
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
        }

        if ((microtime(true) - $start) >= $budget) {
            Cache::put($cacheKey, 0, now()->addMinutes(10));
            return $memo[$cacheKey] = null;
        }

        // 3) Fallback via EUR comme devise pivot
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
                // noop
            }

            if ((microtime(true) - $start) < $budget) {
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

        // 4bis) Fallback local
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
            // ignore
        }

        Cache::put($cacheKey, 0, now()->addMinutes(10));
        $logKey = 'fxlog:' . $from . ':' . $to;
        if (!Cache::has($logKey)) {
            Log::warning('FX conversion failed; fallback to XOF', [
                'pair' => $from . '→' . $to,
                'elapsed' => round(microtime(true) - $start, 3) . 's',
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

        // Pré-remplissage des dates depuis la query string
        // 1) dateRange, ex: ?dateRange=2025-10-15 to 2025-10-18
        $queryDateRange = request()->query('dateRange');
        if ($queryDateRange && is_string($queryDateRange)) {
            $this->dateRange = trim($queryDateRange);
            // parser pour remplir checkIn/checkOut
            $dates = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', $this->dateRange);
            if (is_array($dates) && count($dates) === 2) {
                $ci = trim($dates[0]);
                $co = trim($dates[1]);
                // Validation simple
                if ($ci && $co && $ci < $co) {
                    $this->checkInDate = $ci;
                    $this->checkOutDate = $co;
                }
            }
        }

        // 2) Fallback: paramètres distincts start/end (ex: ?start=2025-10-15&end=2025-10-18)
        if (!$this->checkInDate || !$this->checkOutDate) {
            $start = request()->query('start');
            $end = request()->query('end');
            if (is_string($start) && is_string($end)) {
                try {
                    $ci = \Carbon\Carbon::parse($start)->toDateString();
                    $co = \Carbon\Carbon::parse($end)->toDateString();
                    if ($ci < $co) {
                        $this->checkInDate = $ci;
                        $this->checkOutDate = $co;
                        $this->dateRange = $ci . ' to ' . $co;
                    }
                } catch (\Throwable $e) {
                    // ignore si parse invalide
                }
            }
        }

        // 3) Défaut: aujourd'hui → demain si rien de valide trouvé
        if (!$this->checkInDate || !$this->checkOutDate) {
            $today = \Carbon\Carbon::today();
            $tomorrow = (clone $today)->addDay();
            $this->checkInDate = $today->toDateString();
            $this->checkOutDate = $tomorrow->toDateString();
            $this->dateRange = $this->checkInDate . ' to ' . $this->checkOutDate;
        }

        // 4) Ajuster vers la prochaine période disponible si la sélection chevauche des dates occupées
        try {
            [$adjStart, $adjEnd] = $this->adjustDatesToAvailability($this->checkInDate, $this->checkOutDate);
            if ($adjStart && $adjEnd) {
                $this->checkInDate = $adjStart;
                $this->checkOutDate = $adjEnd;
                $this->dateRange = $adjStart . ' to ' . $adjEnd;
            }
        } catch (\Throwable $e) {
            // silencieux si calcul impossible
        }

        // Convertir la description Markdown en HTML
        if ($this->property && $this->property->description) {
            $this->property->description = Str::markdown($this->property->description);
        }

        if (isset($this->propertyId)) {
            $this->bookings = Booking::where('property_id', $this->propertyId)->get();

            // Pré-sélection d'un type de chambre pour les hôtels (si disponible)
            if ($this->property && $this->property->category && ($this->property->category->name === 'Hôtel' || $this->property->category->name === 'Hotel')) {
                // Ne pas présélectionner de type par défaut: garder la vision globale des dates occupées
                $this->selectedRoomTypeId = null;
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

    /**
     * Propose la prochaine plage disponible à partir d'une plage souhaitée.
     * Conserve la durée initiale (au moins 1 nuit) et décale jusqu'à trouver
     * un bloc de jours consécutifs non occupés (selon $this->occupiedDates).
     * Retourne [start, end] au format Y-m-d.
     */
    private function adjustDatesToAvailability(?string $start, ?string $end): array
    {
        try {
            // Construire le set des jours occupés pour la propriété (accepted OU paid)
            $occupied = [];
            if ($this->property) {
                $bookings = $this->property->bookings()
                    ->where(function ($q) {
                        $q->where('status', 'accepted')
                            ->orWhere('payment_status', 'paid');
                    })
                    ->get(['start_date', 'end_date']);
                foreach ($bookings as $b) {
                    try {
                        $period = new \DatePeriod(
                            new \DateTime($b->start_date),
                            new \DateInterval('P1D'),
                            (new \DateTime($b->end_date))->modify('+1 day')
                        );
                        foreach ($period as $d) {
                            $occupied[$d->format('Y-m-d')] = true;
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }
            $today = Carbon::today();
            $ci = $start ? Carbon::parse($start)->startOfDay() : $today->copy();
            $co = ($end && $end > $start) ? Carbon::parse($end)->startOfDay() : $ci->copy()->addDay();
            if ($ci->lt($today)) {
                $ci = $today->copy();
            }
            $nights = max(1, $co->diffInDays($ci));

            $limit = 365; // garde-fou
            $tries = 0;
            while ($tries < $limit) {
                $ok = true;
                for ($i = 0; $i < $nights; $i++) {
                    $day = $ci->copy()->addDays($i)->toDateString();
                    if (isset($occupied[$day])) {
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    $newStart = $ci->toDateString();
                    $newEnd = $ci->copy()->addDays($nights)->toDateString();
                    return [$newStart, $newEnd];
                }
                $ci = $ci->addDay();
                $tries++;
            }
            // si rien trouvé, proposer demain -> après-demain
            $fallbackStart = max($today->copy(), Carbon::tomorrow());
            return [$fallbackStart->toDateString(), $fallbackStart->copy()->addDay()->toDateString()];
        } catch (\Throwable $e) {
            // En cas d'erreur, retourner la plage d'origine ou défaut 1 nuit
            $ci = ($start && $end && $start < $end) ? $start : Carbon::today()->toDateString();
            $co = ($start && $end && $start < $end) ? $end : Carbon::tomorrow()->toDateString();
            return [$ci, $co];
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

    // Méthode obsolète (décoration calendrier supprimée)

    // Rafraîchir le calendrier quand le type de chambre change
    public function updatedSelectedRoomTypeId($value = null): void
    {
        // Déclenche un événement navigateur pour que le JS relise le JSON et mette à jour Flatpickr
        $this->dispatch('occupied-dates-updated');
        // On ne modifie plus la plage automatiquement: l'utilisateur peut conserver ses dates
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
        // Si des types de chambre existent, type et quantité requis (hôtel ou résidence multi-unités)
        $hasRoomTypes = $this->property && $this->property->roomTypes && $this->property->roomTypes->count() > 0;
        if ($hasRoomTypes) {
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

        // Vérifier l'inventaire/ disponibilité pour le type de chambre si roomTypes
        if ($hasRoomTypes && $this->selectedRoomTypeId) {
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
        // Propriétés sans roomTypes: interdire tout chevauchement global
        if (!$hasRoomTypes) {
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

    // Réservation rapide depuis la ligne du tableau (bouton "Réserver")
    public function quickReserve(int $roomTypeId = null)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Dates obligatoires
        if (!$this->dateRange) {
            $this->addError('dateRange', 'Veuillez sélectionner une plage de dates.');
            LivewireAlert::title('Veuillez choisir vos dates')->error()->show();
            return;
        }
        // Valider et extraire check-in/out
        $dates = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', $this->dateRange);
        if (!is_array($dates) || count($dates) !== 2) {
            $this->addError('dateRange', 'Format de plage de dates invalide.');
            return;
        }
        $this->checkInDate = trim($dates[0]);
        $this->checkOutDate = trim($dates[1]);
        if (!$this->checkInDate || !$this->checkOutDate || $this->checkInDate >= $this->checkOutDate) {
            $this->addError('dateRange', 'La date de départ doit être postérieure à la date d\'arrivée.');
            return;
        }

        $property = $this->property ?? Property::with('roomTypes')->find($this->propertyId);
        if (!$property) {
            $this->addError('dateRange', 'Propriété introuvable.');
            return;
        }

        // Déterminer s'il y a des types de chambre
        $hasRoomTypes = $property->roomTypes && $property->roomTypes->count() > 0;

        // Forcer le type de chambre si la propriété a des roomTypes
        if ($hasRoomTypes) {
            $this->selectedRoomTypeId = $roomTypeId ?? $this->selectedRoomTypeId;
            if (!$this->selectedRoomTypeId) {
                $this->addError('selectedRoomTypeId', 'Veuillez sélectionner un type de chambre.');
                return;
            }
            if (!$property->roomTypes->firstWhere('id', (int) $this->selectedRoomTypeId)) {
                $this->addError('selectedRoomTypeId', 'Type de chambre introuvable.');
                return;
            }
        }

        // Vérifications communes d\'inventaire/disponibilité + quantité
        $this->quantity = max(1, (int) $this->quantity);

        // Reprendre la logique d\'addBooking
        // Vérifier l'inventaire/ disponibilité pour le type de chambre si roomTypes
        if ($hasRoomTypes && $this->selectedRoomTypeId) {
            $roomType = $property->roomTypes->firstWhere('id', (int) $this->selectedRoomTypeId);
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
            if (($overlap + $this->quantity) > (int) $roomType->inventory) {
                $this->addError('quantity', 'La quantité demandée dépasse la disponibilité pour ces dates.');
                return;
            }
        }
        // Propriétés sans roomTypes: interdiction de chevauchement global
        if (!$hasRoomTypes) {
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

        // Propriétaire ne peut pas réserver
        if ($property->user_id == Auth::id()) {
            LivewireAlert::title('Vous ne pouvez pas réserver une de vos propriétés')->error()->show();
            return;
        }

        // Date d\'entrée >= aujourd\'hui
        if ($this->checkInDate < Carbon::today()->toDateString()) {
            LivewireAlert::title('La date d\'entrée ne peut pas être inférieure à la date du jour')->error()->show();
            return;
        }

        // Prix total
        $this->calculateTotalPrice();

        // Création de la réservation (identique à addBooking)
        $booking = Booking::create([
            'property_id' => $this->propertyId,
            'room_type_id' => $this->selectedRoomTypeId,
            'quantity' => max(1, (int) $this->quantity),
            'user_id' => Auth::id(),
            'start_date' => $this->checkInDate,
            'end_date' => $this->checkOutDate,
            'total_price' => $this->totalPrice,
            'status' => 'pending',
        ]);

        // Créer la conversation admin identique à addBooking
        $property = Property::find($this->propertyId);
        $adminGroupConversation = \App\Models\Conversation::create([
            'is_admin_channel' => true,
            'user_id' => Auth::id(),
            'owner_id' => 5,
            'booking_id' => $booking->id
        ]);

        $userName = Auth::user()->name ?? 'Utilisateur';
        $userMessage = Message::create([
            'conversation_id' => $adminGroupConversation->id,
            'sender_id' => Auth::id(),
            'receiver_id' => 5,
            'content' => 'Bonjour, je suis Mr/Mme ' . $userName . ', je souhaite réserver ' . ($property ? $property->name : '') . ' du ' . $this->checkInDate . ' au ' . $this->checkOutDate . '. Merci de confirmer la disponibilité.',
        ]);
        try {
            broadcast(new MessageSent($userMessage));
        } catch (\Throwable $e) {
        }

        // Auto-réponse admin (même logique)
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
                try {
                    broadcast(new MessageSent($autoMessage));
                } catch (\Throwable $e) {
                }
            }
        } catch (\Throwable $e) {
        }

        // Notifications mail (
        try {
            \Illuminate\Support\Facades\Mail::raw(
                "Votre demande de reservation à bien été soumise, nous vérifions la disponibilité...",
                function ($message) {
                    $message->to(Auth::user()->email)->subject('Demande de réservation soumise');
                }
            );
        } catch (\Exception $e) {
        }
        try {
            $admin = User::find(5);
            if ($admin) {
                \Illuminate\Support\Facades\Mail::raw(
                    "Vous avez une demande de reservation en attente.",
                    function ($message) use ($admin) {
                        $message->to($admin->email)->subject('Nouvelle demande de réservation');
                    }
                );
                $admin->notify(new \App\Notifications\BookingRequestNotification($booking));
            }
        } catch (\Exception $e) {
        }

        // Rediriger vers le chat comme addBooking
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
        return view('livewire.booking-manager', compact('properties'));
    }

    /**
     * Retourne un tableau associatif des dates occupées par type de chambre.
     * Format: [ 'global' => [...], room_type_id(int) => [...dates Y-m-d...] ]
     */
    public function getOccupiedDatesByRoomTypeProperty(): array
    {
        if (!$this->property) return [];

        $map = [];
        // Global: union de toutes les réservations acceptées/payées de la propriété
        $globalBookings = $this->property->bookings()
            ->where(function ($q) {
                $q->where('status', 'accepted')
                    ->orWhere('payment_status', 'paid');
            })
            ->get(['start_date', 'end_date']);
        $globalDates = [];
        foreach ($globalBookings as $b) {
            try {
                $period = new \DatePeriod(
                    new \DateTime($b->start_date),
                    new \DateInterval('P1D'),
                    (new \DateTime($b->end_date))->modify('+0 day')
                );
                foreach ($period as $d) {
                    $globalDates[] = $d->format('Y-m-d');
                }
            } catch (\Throwable $e) {
            }
        }
        $globalDates = array_values(array_unique($globalDates));
        sort($globalDates);
        $map['global'] = $globalDates;

        // Par room type (toutes catégories si des roomTypes existent)
        $rts = $this->property->roomTypes ?? collect();
        foreach ($rts as $rt) {
            $rtBookings = Booking::query()
                ->where('property_id', $this->property->id)
                ->where('room_type_id', $rt->id)
                ->where(function ($q) {
                    $q->where('status', 'accepted')
                        ->orWhere('payment_status', 'paid');
                })
                ->get(['start_date', 'end_date']);
            $dates = [];
            foreach ($rtBookings as $b) {
                try {
                    $period = new \DatePeriod(
                        new \DateTime($b->start_date),
                        new \DateInterval('P1D'),
                        (new \DateTime($b->end_date))->modify('+0 day')
                    );
                    foreach ($period as $d) {
                        $dates[] = $d->format('Y-m-d');
                    }
                } catch (\Throwable $e) {
                }
            }
            $dates = array_values(array_unique($dates));
            sort($dates);
            $map[(int)$rt->id] = $dates;
        }

        return $map;
    }

    // Transforme le formulaire en moteur de recherche de dates
    public function searchDates()
    {
        // Si vide: défaut aujourd'hui -> demain
        if (!$this->dateRange || !is_string($this->dateRange)) {
            $today = Carbon::today()->toDateString();
            $tomorrow = Carbon::tomorrow()->toDateString();
            $this->dateRange = $today . ' to ' . $tomorrow;
        }
        // Normalisation & validation
        $parts = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', (string) $this->dateRange);
        if (!is_array($parts) || count($parts) !== 2) {
            $this->addError('dateRange', 'Format de plage de dates invalide.');
            return;
        }
        $ci = trim($parts[0]);
        $co = trim($parts[1]);
        if (!$ci || !$co) {
            $this->addError('dateRange', 'Veuillez sélectionner une plage de dates.');
            return;
        }
        try {
            $start = Carbon::parse($ci)->toDateString();
            $end = Carbon::parse($co)->toDateString();
            if ($start >= $end) {
                $this->addError('dateRange', 'La date de départ doit être postérieure à la date d\'arrivée.');
                return;
            }
            $this->checkInDate = $start;
            $this->checkOutDate = $end;
            // Conserver la plage choisie par l'utilisateur sans auto-ajustement
            $this->dateRange = $this->checkInDate . ' to ' . $this->checkOutDate;
            // Rafraîchir les dates occupées côté front
            $this->dispatch('occupied-dates-updated');
            // Événements front: retour utilisateur + synchro Flatpickr (avec détail de la plage ISO)
            $this->dispatch('dates-search-completed');
            $this->dispatch('date-range-updated', dateRange: $this->dateRange);
        } catch (\Throwable $e) {
            $this->addError('dateRange', 'Dates invalides.');
        }
    }
}
