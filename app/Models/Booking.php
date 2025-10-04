<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;
    protected $fillable = [
        'property_id',
        'user_id',
        'start_date',
        'end_date',
        'total_price',
        'status',
        'payment_transaction_id',
        'payment_status',
        'paid_at',
        'review_reminder_sent_at',
        'review_reminder_sent_7d_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'review_reminder_sent_at' => 'datetime',
        'review_reminder_sent_7d_at' => 'datetime',
    ];

    public function markAsPaid(?string $txId = null): void
    {
        $this->payment_status = 'paid';
        if ($txId) {
            $this->payment_transaction_id = $txId;
        }
        $this->paid_at = now();
        $this->save();
    }

    /**
     * Calcule le prix total de la réservation selon les dates et le prix de la propriété
     * @return float|int|null
     */
    public function calculateTotalPrice()
    {
        // S'assurer que la relation property est chargée
        $property = $this->property ?? $this->loadMissing('property')->property;
        if (!$property || !$this->start_date || !$this->end_date) {
            return null;
        }
        $checkIn = strtotime($this->start_date);
        $checkOut = strtotime($this->end_date);
        $days = ($checkOut - $checkIn) / 86400;
        if ($days < 1) {
            $days = 1;
        }
        return $days * $property->price_per_night;
    }

    public function property()
    {
        return $this->belongsTo(Property::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
