<?php

namespace App\Livewire;

use Livewire\Component;

use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class UserCanceledReservationsCity extends Component
{
    public $city;
    public $canceled;

    public function mount($city)
    {
        $this->city = urldecode($city);
        $user = Auth::user();
        $this->canceled = Booking::with('property')
            ->whereHas('property', function ($q) {
                $q->where('city', $this->city);
            })
            ->where('user_id', $user->id)
            ->where('status', 'cancelled')
            ->orderByDesc('start_date')
            ->get();
    }

    public function deleteBooking($bookingId)
    {
        $booking = Booking::find($bookingId);
        if ($booking && $booking->user_id === Auth::id()) {
            $booking->delete();
            // Rafraîchir la liste locale
            $this->canceled = $this->canceled->filter(fn($b) => $b->id !== $bookingId);
            session()->flash('success', 'Réservation supprimée avec succès.');
        }
    }

    public function render()
    {
        return view('livewire.user-canceled-reservations-city', [
            'city' => $this->city,
            'canceled' => $this->canceled,
        ]);
    }
}
