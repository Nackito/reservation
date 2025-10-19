<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Str;
use App\Models\Property;
use App\Models\Booking;
use App\Models\Message;
use App\Models\Reviews;
use App\Models\User;
use App\Models\SearchState;
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
    protected $listeners = [
        'persist-date-range' => 'persistDateRange',
    ];
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
        // Mémoïsation simple pour la requête courante
        static $memo = [];
        $from = strtoupper((string) $from);
        $to = strtoupper((string) $to);
        if (!$from || !$to) return null;
        if ($from === $to) return 1.0;

        $cacheKey = "fx:{$from}:{$to}";
        if (array_key_exists($cacheKey, $memo)) {
            return $memo[$cacheKey];
        }

        // 1) Cache
        $cached = Cache::get($cacheKey);
        if (is_numeric($cached) && (float) $cached > 0) {
            return $memo[$cacheKey] = (float) $cached;
        }

        // 2) API directe: exchangerate.host/convert
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
            // ignore
        }

        // 3) Pivot via EUR: rate(from->to) = rate(EUR->to) / rate(EUR->from)
        try {
            $resp = Http::acceptJson()
                ->timeout(4)
                ->connectTimeout(2)
                ->retry(1, 200)
                ->get('https://api.exchangerate.host/latest', [
                    'base' => 'EUR',
                    'symbols' => $from . ',' . $to,
                ]);
            if ($resp->successful()) {
                $j = $resp->json();
                $rates = $j['rates'] ?? [];
                $eurToFrom = (float) ($rates[$from] ?? 0);
                $eurToTo = (float) ($rates[$to] ?? 0);
                if ($from === 'EUR') $eurToFrom = 1.0;
                if ($to === 'EUR') $eurToTo = 1.0;
                if ($eurToFrom > 0 && $eurToTo > 0) {
                    $rate = $eurToTo / $eurToFrom;
                    Cache::put($cacheKey, $rate, now()->addHours(6));
                    return $memo[$cacheKey] = $rate;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // 4) Fallback local: resources/fx/rates.json (optionnel)
        try {
            $path = resource_path('fx/rates.json');
            if (is_file($path)) {
                $json = json_decode(file_get_contents($path), true);
                $base = strtoupper($json['base'] ?? 'XOF');
                $table = $json['rates'] ?? [];
                if ($base === $from && isset($table[$to]) && is_numeric($table[$to])) {
                    $rate = (float) $table[$to];
                    if ($rate > 0) {
                        Cache::put($cacheKey, $rate, now()->addHours(6));
                        return $memo[$cacheKey] = $rate;
                    }
                } elseif (isset($table[$from]) && isset($table[$to]) && (float)$table[$from] > 0) {
                    // Si le fichier a un autre base, tenter un pivot simple
                    $rate = ((float)$table[$to]) / ((float)$table[$from]);
                    if ($rate > 0) {
                        Cache::put($cacheKey, $rate, now()->addHours(6));
                        return $memo[$cacheKey] = $rate;
                    }
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Échec: mettre un échec court en cache pour éviter le spam
        Cache::put($cacheKey, 0, now()->addMinutes(10));
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

        // Source de vérité: la DB uniquement
        try {
            $sessionId = session()->getId();
            $propertyId = $this->propertyId;

            if (Auth::check()) {
                // Migration session -> user si besoin
                $sessionState = SearchState::query()
                    ->whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                    ->orderByDesc('updated_at')
                    ->first();
                $userState = SearchState::query()
                    ->where('user_id', Auth::id())
                    ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                    ->orderByDesc('updated_at')
                    ->first();

                if ($sessionState) {
                    if (!$userState) {
                        // Réattribuer la ligne session au user connecté
                        $sessionState->user_id = Auth::id();
                        $sessionState->session_id = null;
                        $sessionState->save();
                        $userState = $sessionState;
                    } elseif ($sessionState->updated_at > $userState->updated_at) {
                        // Mettre à jour la ligne user avec la plus récente
                        $userState->fill([
                            'date_range' => $sessionState->date_range,
                            'check_in'   => $sessionState->check_in,
                            'check_out'  => $sessionState->check_out,
                        ])->save();
                        // Nettoyer l'ancienne session
                        try {
                            $sessionState->delete();
                        } catch (\Throwable $e) {
                        }
                    } else {
                        // Session plus ancienne: on peut la supprimer
                        try {
                            $sessionState->delete();
                        } catch (\Throwable $e) {
                        }
                    }
                }

                // Si aucun état user: initialiser aujourd'hui→demain en DB (exigence "après login")
                if (!$userState) {
                    $today = Carbon::today()->toDateString();
                    $tomorrow = Carbon::tomorrow()->toDateString();
                    $userState = SearchState::create([
                        'user_id'    => Auth::id(),
                        'session_id' => null,
                        'property_id' => $propertyId,
                        'date_range' => $today . ' to ' . $tomorrow,
                        'check_in'   => $today,
                        'check_out'  => $tomorrow,
                    ]);
                }

                // Appliquer l'état user dans le composant
                if ($userState && $userState->check_in && $userState->check_out && $userState->check_in < $userState->check_out) {
                    $this->checkInDate = (string) $userState->check_in;
                    $this->checkOutDate = (string) $userState->check_out;
                    $this->dateRange = $this->checkInDate . ' to ' . $this->checkOutDate;
                }
            } else {
                // Invité: lire seulement l'état session si présent (pas de défaut)
                $last = SearchState::query()
                    ->whereNull('user_id')
                    ->where('session_id', $sessionId)
                    ->when($propertyId, fn($q) => $q->where('property_id', $propertyId))
                    ->orderByDesc('updated_at')
                    ->first();
                if ($last && $last->check_in && $last->check_out && $last->check_in < $last->check_out) {
                    $this->checkInDate = (string) $last->check_in;
                    $this->checkOutDate = (string) $last->check_out;
                    $this->dateRange = $this->checkInDate . ' to ' . $this->checkOutDate;
                } elseif ($last && $last->date_range) {
                    $parts = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', (string) $last->date_range);
                    if (is_array($parts) && count($parts) === 2) {
                        $ci = trim($parts[0]);
                        $co = trim($parts[1]);
                        if ($ci && $co && $ci < $co) {
                            $this->checkInDate = $ci;
                            $this->checkOutDate = $co;
                            $this->dateRange = $ci . ' to ' . $co;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // silencieux
        }

        // 4) (Optionnel) Ajustement auto vers la prochaine plage disponible — désactivé par défaut
        // Le souhait: l'affichage ne doit pas tenir compte des disponibilités, seule la recherche le fait.
        if (config('app.auto_adjust_dates', false) && $this->checkInDate && $this->checkOutDate) {
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
    // Récapitulatif de réservation (modal de confirmation)
    public $showSummaryModal = false;
    public $unitPrice = null; // en XOF
    public $unitPriceConverted = null; // dans la devise utilisateur si dispo
    public $unitCurrency = 'XOF';

    // computeNights supprimée à la demande: on n'affiche plus le nombre de nuits

    // Méthode addBooking obsolète et non utilisée supprimée (flux remplacé par openSummary/confirmReservation)

    /**
     * Ouvre le récapitulatif avant soumission définitive.
     * Si roomTypeId est fourni (cas hôtel), on le sélectionne.
     */
    public function openSummary(int $roomTypeId = null)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Exiger une plage de dates valide
        if (!$this->dateRange) {
            $this->addError('dateRange', 'Veuillez sélectionner une plage de dates.');
            return;
        }
        $dates = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', (string)$this->dateRange);
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

        $hasRoomTypes = $property->roomTypes && $property->roomTypes->count() > 0;
        if ($hasRoomTypes) {
            if ($roomTypeId !== null) {
                $this->selectedRoomTypeId = (int) $roomTypeId;
            }
            if (!$this->selectedRoomTypeId || !$property->roomTypes->firstWhere('id', (int)$this->selectedRoomTypeId)) {
                $this->addError('selectedRoomTypeId', 'Veuillez sélectionner un type de chambre.');
                return;
            }
        } else {
            $this->selectedRoomTypeId = null; // Non applicable
        }

        // Quantité par défaut
        $this->quantity = max(1, (int)($this->quantity ?: 1));

        // Calculs: prix unitaire, total et conversions

        // Prix unitaire (XOF) selon type si applicable
        $this->unitPrice = (float) ($property->price_per_night ?? 0);
        if ($this->selectedRoomTypeId) {
            $rt = $property->roomTypes->firstWhere('id', (int) $this->selectedRoomTypeId);
            if ($rt && $rt->price_per_night !== null) {
                $this->unitPrice = (float)$rt->price_per_night;
            }
        }
        $this->unitCurrency = 'XOF';
        $c = $this->getConvertedPrice($this->unitPrice);
        $this->unitPriceConverted = $c['amount'];
        // Calcul du total
        $this->calculateTotalPrice();

        // Ouvrir le modal
        $this->showSummaryModal = true;
    }

    /** Ferme le récapitulatif sans soumettre */
    public function closeSummary(): void
    {
        $this->showSummaryModal = false;
    }

    /**
     * Confirme la réservation après récapitulatif et envoie les notifications.
     */
    public function confirmReservation()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Valider dates
        if (!$this->dateRange) {
            $this->addError('dateRange', 'Veuillez sélectionner une plage de dates.');
            return;
        }
        $dates = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', (string)$this->dateRange);
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
        $hasRoomTypes = $property->roomTypes && $property->roomTypes->count() > 0;

        // Room type requis si applicable (le user peut avoir modifié la quantité dans le modal)
        if ($hasRoomTypes) {
            if (!$this->selectedRoomTypeId || !$property->roomTypes->firstWhere('id', (int)$this->selectedRoomTypeId)) {
                $this->addError('selectedRoomTypeId', 'Veuillez sélectionner un type de chambre.');
                return;
            }
        }

        $this->quantity = max(1, (int)$this->quantity);

        // Disponibilités
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
        } else {
            // propriétés sans roomTypes: pas de chevauchement global
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
                // UI gère déjà l'indisponibilité
                return;
            }
        }

        // Propriétaire ne peut pas réserver
        if ($property->user_id == Auth::id()) {
            LivewireAlert::title('Vous ne pouvez pas réserver une de vos propriétés')->error()->show();
            return;
        }

        // Date d'entrée >= aujourd'hui
        if ($this->checkInDate < Carbon::today()->toDateString()) {
            LivewireAlert::title('La date d\'entrée ne peut pas être inférieure à la date du jour')->error()->show();
            return;
        }

        // Recalculer total (au cas où la quantité a changé dans le modal)
        $this->calculateTotalPrice();

        // Rien à faire pour les nuits: on ne l'affiche plus

        // Créer la réservation
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

        // Conversation admin groupée (comme dans quickReserve)
        $adminGroupConversation = \App\Models\Conversation::create([
            'is_admin_channel' => true,
            'user_id' => Auth::id(),
            'owner_id' => 5,
            'booking_id' => $booking->id
        ]);
        // Construire un récapitulatif riche
        $rtName = null;
        if ($this->selectedRoomTypeId) {
            $rt = $property->roomTypes->firstWhere('id', (int)$this->selectedRoomTypeId);
            $rtName = $rt?->name;
        }
        // Dates FR (ex: Mercredi 20 Octobre 2025)
        try {
            $ciFr = Str::title(Carbon::parse($this->checkInDate)->locale('fr')->translatedFormat('l d F Y'));
            $coFr = Str::title(Carbon::parse($this->checkOutDate)->locale('fr')->translatedFormat('l d F Y'));
        } catch (\Throwable $e) {
            $ciFr = $this->checkInDate;
            $coFr = $this->checkOutDate;
        }

        $summaryLines = [];
        $summaryLines[] = 'Nouvelle demande de réservation';
        $summaryLines[] = '• Établissement: ' . ($property->name ?? '—');
        if ($rtName) {
            $summaryLines[] = '• Type de chambre: ' . $rtName;
        }
        $summaryLines[] = '• Quantité: ' . max(1, (int)$this->quantity);
        $summaryLines[] = '• Séjour: ' . $ciFr . ' → ' . $coFr;
        // Prix unitaire + total: XOF et devise utilisateur
        $unitConv = $this->getConvertedPrice($this->unitPrice ?? 0);
        $summaryLines[] = '• Prix unitaire: ' . number_format((float)($this->unitPrice ?? 0), 2) . ' XOF' . ' (' . number_format((float)$unitConv['amount'], 2) . ' ' . $unitConv['currency'] . ')';
        $summaryLines[] = '• Total: ' . number_format((float)$this->totalPrice, 2) . ' XOF' . ' (' . number_format((float)$this->convertedPrice, 2) . ' ' . $this->convertedCurrency . ')';
        $summaryText = implode("\n", $summaryLines);

        $userName = Auth::user()->name ?? 'Utilisateur';
        $userMessage = Message::create([
            'conversation_id' => $adminGroupConversation->id,
            'sender_id' => Auth::id(),
            'receiver_id' => 5,
            'content' => $summaryText,
        ]);
        try {
            broadcast(new MessageSent($userMessage));
        } catch (\Throwable $e) {
        }

        // Auto-réponse admin
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

        // Emails enrichis
        try {
            $body = $summaryText . "\n\nMerci de votre demande. Nous revenons vers vous rapidement.";
            \Illuminate\Support\Facades\Mail::raw($body, function ($message) {
                $message->to(Auth::user()->email)->subject('Demande de réservation soumise');
            });
        } catch (\Exception $e) {
        }
        try {
            $admin = User::find(5);
            if ($admin) {
                $body = $summaryText . "\n\nVeuillez vérifier la disponibilité et répondre au client.";
                \Illuminate\Support\Facades\Mail::raw($body, function ($message) use ($admin) {
                    $message->to($admin->email)->subject('Nouvelle demande de réservation');
                });
                $admin->notify(new \App\Notifications\BookingRequestNotification($booking));
            }
        } catch (\Exception $e) {
        }

    // Fermer le modal et rediriger vers la conversation créée
    $this->showSummaryModal = false;
    return redirect()->route('user.chat', ['conversation_id' => $adminGroupConversation->id]);
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
                // Ancienne méthode supprimée: plus d'erreur inline côté formulaire.
                // L'UI masque le bouton et affiche un message d'indisponibilité.
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
        // Dates FR pour le message utilisateur
        try {
            $ciFr = Str::title(Carbon::parse($this->checkInDate)->locale('fr')->translatedFormat('l d F Y'));
            $coFr = Str::title(Carbon::parse($this->checkOutDate)->locale('fr')->translatedFormat('l d F Y'));
        } catch (\Throwable $e) {
            $ciFr = $this->checkInDate;
            $coFr = $this->checkOutDate;
        }
        $userMessage = Message::create([
            'conversation_id' => $adminGroupConversation->id,
            'sender_id' => Auth::id(),
            'receiver_id' => 5,
            'content' => 'Bonjour, je suis Mr/Mme ' . $userName . ', je souhaite réserver ' . ($property ? $property->name : '') . ' du ' . $ciFr . ' au ' . $coFr . '. Merci de confirmer la disponibilité.',
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

    // Rediriger vers la conversation créée
    return redirect()->route('user.chat', ['conversation_id' => $adminGroupConversation->id]);
    }

    // toggleWishlist supprimée: non utilisée dans cette vue et relation User::wishlists absente

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
        // Si vide: exiger une sélection plutôt que de forcer un défaut
        if (!$this->dateRange || !is_string($this->dateRange)) {
            $this->addError('dateRange', 'Veuillez sélectionner une plage de dates.');
            return;
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

            // Persistance DB de l'état de recherche (user_id ou session_id + property)
            try {
                $pid = (int) ($this->propertyId ?? ($this->property->id ?? 0));
                if ($pid <= 0) {
                    Log::warning('SearchState upsert skipped: missing property_id');
                } else {
                    $isAuth = Auth::check();
                    $conditions = $isAuth
                        ? ['user_id' => Auth::id(), 'property_id' => $pid]
                        : ['user_id' => null, 'session_id' => session()->getId(), 'property_id' => $pid];
                    $values = [
                        'date_range' => $this->dateRange,
                        'check_in'   => $this->checkInDate,
                        'check_out'  => $this->checkOutDate,
                    ];
                    // Pour être sûr que toutes les colonnes sont correctement remplies
                    if (!$isAuth) {
                        $values['session_id'] = $conditions['session_id'];
                    }
                    if ($isAuth) {
                        $values['user_id'] = $conditions['user_id'];
                    }
                    $values['property_id'] = $pid;

                    $state = SearchState::updateOrCreate($conditions, $values);
                    Log::debug('SearchState upserted', [
                        'conditions' => $conditions,
                        'values' => $values,
                        'id' => $state->id ?? null,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error('SearchState upsert failed', [
                    'error' => $e->getMessage(),
                ]);
            }
            // Rafraîchir les dates occupées côté front
            $this->dispatch('occupied-dates-updated');
            // Événements front: retour utilisateur + synchro Flatpickr (avec détail de la plage ISO)
            $this->dispatch('dates-search-completed');
            $this->dispatch('date-range-updated', dateRange: $this->dateRange);
        } catch (\Throwable $e) {
            $this->addError('dateRange', 'Dates invalides.');
        }
    }

    /**
     * Persiste immédiatement la plage transmise par le front (Flatpickr) en DB.
     * Utilisé pour que la DB reflète le dernier choix avant même le clic sur Rechercher.
     */
    public function persistDateRange(string $isoRange = null): void
    {
        try {
            $isoRange = trim((string)$isoRange);
            if ($isoRange === '') return;
            $parts = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', $isoRange);
            if (!is_array($parts) || count($parts) !== 2) return;
            $ci = Carbon::parse(trim($parts[0]))->toDateString();
            $co = Carbon::parse(trim($parts[1]))->toDateString();
            if ($ci >= $co) return;

            $this->checkInDate = $ci;
            $this->checkOutDate = $co;
            $this->dateRange = $ci . ' to ' . $co;

            // upsert DB
            $pid = (int) ($this->propertyId ?? ($this->property->id ?? 0));
            if ($pid <= 0) {
                Log::warning('persistDateRange skipped: missing property_id');
                return;
            }
            $isAuth = Auth::check();
            $conditions = $isAuth
                ? ['user_id' => Auth::id(), 'property_id' => $pid]
                : ['user_id' => null, 'session_id' => session()->getId(), 'property_id' => $pid];
            $values = [
                'date_range' => $this->dateRange,
                'check_in'   => $this->checkInDate,
                'check_out'  => $this->checkOutDate,
                'property_id' => $pid,
            ];
            if (!$isAuth) {
                $values['session_id'] = $conditions['session_id'];
            }
            if ($isAuth) {
                $values['user_id'] = $conditions['user_id'];
            }
            $state = SearchState::updateOrCreate($conditions, $values);
            Log::debug('SearchState persisted (live change)', ['id' => $state->id ?? null]);
        } catch (\Throwable $e) {
            Log::error('persistDateRange failed', ['m' => $e->getMessage()]);
        }
    }

    /**
     * Persiste la plage de dates en se basant UNIQUEMENT sur la session courante (session_id),
     * sans tenir compte de l'état d'authentification. Utile en fallback côté client.
     */
    public function persistDateRangeBySession(string $isoRange = null): void
    {
        try {
            $isoRange = trim((string)$isoRange);
            if ($isoRange === '') return;
            $parts = preg_split('/\s+(?:to|à|au|\-|–|—)\s+/ui', $isoRange);
            if (!is_array($parts) || count($parts) !== 2) return;
            $ci = Carbon::parse(trim($parts[0]))->toDateString();
            $co = Carbon::parse(trim($parts[1]))->toDateString();
            if ($ci >= $co) return;

            // Mettre à jour l'état local pour cohérence UI
            $this->checkInDate = $ci;
            $this->checkOutDate = $co;
            $this->dateRange = $ci . ' to ' . $co;

            $pid = (int) ($this->propertyId ?? ($this->property->id ?? 0));
            if ($pid <= 0) {
                Log::warning('persistDateRangeBySession skipped: missing property_id');
                return;
            }
            $sessId = session()->getId();
            $conditions = [
                'user_id' => null,
                'session_id' => $sessId,
                'property_id' => $pid,
            ];
            $values = [
                'date_range' => $this->dateRange,
                'check_in'   => $this->checkInDate,
                'check_out'  => $this->checkOutDate,
                'session_id' => $sessId,
                'property_id' => $pid,
            ];
            $state = SearchState::updateOrCreate($conditions, $values);
            Log::debug('SearchState persisted by session', ['id' => $state->id ?? null]);
        } catch (\Throwable $e) {
            Log::error('persistDateRangeBySession failed', ['m' => $e->getMessage()]);
        }
    }
}
