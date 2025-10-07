<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Reviews;
use Illuminate\Support\Str;

class Property extends Model
{
    use HasFactory;

    /**
     * Liste canonique des commodités: clé (stockage/filtre) => label (affichage).
     */
    public const FEATURES = [
        'wifi' => 'Wi‑Fi',
        'parking' => 'Parking',
        'climatisation' => 'Climatisation',
        'piscine' => 'Piscine',
        'jardin' => 'Jardin',
        'balcon' => 'Balcon',
        'ascenseur' => 'Ascenseur',
        'meuble' => 'Meublé',
        'terrasse' => 'Terrasse',
        'barbecue' => 'Barbecue',
        'salle de sport' => 'Salle de sport',
        'securite' => 'Sécurité 24/7',
        'cuisine equipee' => 'Cuisine équipée',
        'salle de bain privee' => 'Salle de bain privée',
        'vue sur mer' => 'Vue sur mer',
        'jacuzzi' => 'Jacuzzi',
        'canal+' => 'Canal+',
        'netflix' => 'Netflix',
        'tv' => 'TV',
    ];

    public static function amenityLabel(string $key): string
    {
        if (array_key_exists($key, self::FEATURES)) {
            return self::FEATURES[$key];
        }
        // fallback simple: underscores -> espaces + majuscule initiale
        $pretty = str_replace('_', ' ', $key);
        return mb_convert_case($pretty, MB_CASE_TITLE, 'UTF-8');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    protected $fillable = ['name', 'description', 'price_per_night', 'user_id', 'property_type', 'number_of_rooms', 'city', 'municipality', 'district', 'status', 'slug', 'features', 'latitude', 'longitude', 'standing'];

    public function images()
    {
        return $this->hasMany(PropertyImage::class);
    }

    public function firstImage()
    {
        return $this->images()->orderBy('id')->first(); // Récupère la première image
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function reviews()
    {
        return $this->hasMany(Reviews::class);
    }

    protected $casts = [
        'features' => 'array',
        'standing' => 'integer',
    ];

    /**
     * Prix de départ: pour un Hôtel, le plus petit prix/nuit des types de chambre.
     * Pour les autres catégories, le price_per_night de la propriété.
     */
    public function getStartingPriceAttribute(): ?float
    {
        $categoryName = optional($this->category)->name;
        if ($categoryName === 'Hôtel' || $categoryName === 'Hotel') {
            if ($this->relationLoaded('roomTypes')) {
                $min = collect($this->roomTypes)
                    ->pluck('price_per_night')
                    ->filter(fn($p) => $p !== null)
                    ->min();
            } else {
                $min = $this->roomTypes()
                    ->whereNotNull('price_per_night')
                    ->min('price_per_night');
            }
            return $min !== null ? (float) $min : null;
        }
        return $this->price_per_night !== null ? (float) $this->price_per_night : null;
    }

    /**
     * Normalise une valeur arbitraire de caractéristique vers une clé canonique.
     * Retourne null si aucune clé ne correspond.
     */
    public static function normalizeFeatureKey($value): ?string
    {
        $result = null;
        if ($value === null || $value === '') {
            return null;
        }

        $lower = self::toLowerString($value);

        // 1) correspondances explicites
        $explicit = self::explicitFeatureMap();
        if (isset($explicit[$lower])) {
            $result = $explicit[$lower];
        }

        // 2) déjà une clé canonique
        if ($result === null && array_key_exists($lower, self::FEATURES)) {
            $result = $lower;
        }

        // 3) normaliser en slug-like
        $norm = null;
        if ($result === null) {
            $norm = self::normalizeToKey($lower);
            if (array_key_exists($norm, self::FEATURES)) {
                $result = $norm;
            }
        }

        // 4) faire correspondre via label normalisé
        if ($result === null) {
            $norm = $norm ?? self::normalizeToKey($lower);
            $labelMap = self::labelNormalizedMap();
            if (isset($labelMap[$norm])) {
                $result = $labelMap[$norm];
            }
        }

        return $result;
    }

    private static function toLowerString($value): string
    {
        $raw = is_string($value) ? $value : (string) $value;
        return mb_strtolower(trim($raw), 'UTF-8');
    }

    private static function normalizeToKey(string $lower): string
    {
        $ascii = Str::ascii($lower);
        $ascii = str_replace('+', ' plus', $ascii); // canal+ -> canal plus
        $norm = preg_replace('/[^a-z0-9]+/i', '_', $ascii);
        $norm = trim($norm, '_');
        return str_replace(['wi_fi'], ['wifi'], $norm);
    }

    private static function explicitFeatureMap(): array
    {
        return [
            'sécurité' => 'securite',
            'securite' => 'securite',
            'cuisine équipée' => 'cuisine_equipee',
            'cuisine equipee' => 'cuisine_equipee',
            'salle_de_bain privée' => 'salle_de_bain_privee',
            'salle de bain privee' => 'salle_de_bain_privee',
            'canal+' => 'canal_plus',
            'canal plus' => 'canal_plus',
            'wi-fi' => 'wifi',
            'wi fi' => 'wifi',
        ];
    }

    private static function labelNormalizedMap(): array
    {
        $map = [];
        foreach (self::FEATURES as $key => $label) {
            $labelLower = mb_strtolower($label, 'UTF-8');
            $labelNorm = self::normalizeToKey($labelLower);
            $map[$labelNorm] = $key;
        }
        return $map;
    }

    /**
     * Normalise un ensemble de caractéristiques (array|string|null) en tableau de clés canoniques uniques.
     */
    public static function normalizeFeatureKeys($values): array
    {
        $arr = [];
        if (is_array($values)) {
            $arr = $values;
        } elseif (is_string($values)) {
            $decoded = json_decode($values, true);
            $arr = is_array($decoded) ? $decoded : [];
        }

        $keys = [];
        foreach ($arr as $f) {
            $k = self::normalizeFeatureKey($f);
            if ($k !== null) {
                $keys[$k] = true; // unique
            }
        }
        return array_keys($keys);
    }

    /**
     * Normalise les clés des caractéristiques pour rester aligné avec les options Filament.
     * - Mappe les anciennes valeurs (avec accents/signes) vers les nouvelles clés "safe".
     * - Évite que l’édition d’un enregistrement existant fasse échouer la validation.
     */
    public function getFeaturesAttribute($value)
    {
        return self::normalizeFeatureKeys($value);
    }

    public function setFeaturesAttribute($value)
    {
        $this->attributes['features'] = json_encode(self::normalizeFeatureKeys($value));
    }
}
