<?php

namespace App\Livewire;


use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Reviews;

class UserReservationsCity extends Component
{
    public $city;
    public $residences;

    public function mount($city)
    {
        $user = Auth::user();
        $decodedCity = urldecode($city);
        $this->city = $decodedCity;
        $this->residences = Booking::with(['property', 'property.reviews' => function ($q) use ($user) {
            $q->where('user_id', $user->id);
        }])
            ->whereHas('property', function ($q) use ($decodedCity) {
                $q->where('city', $decodedCity);
            })
            ->where('user_id', $user->id)
            ->whereIn('status', ['past', 'accepted'])
            ->orderByDesc('start_date')
            ->get();
    }

    public function deleteBooking($bookingId)
    {
        $booking = Booking::find($bookingId);
        if ($booking && $booking->user_id === Auth::id()) {
            $booking->delete();
            session()->flash('success', 'Réservation supprimée avec succès.');
            $this->mount($this->city); // Rafraîchir la liste
        }
    }

    public function render()
    {
        return view('livewire.user-reservations-city', [
            'city' => $this->city,
            'residences' => $this->residences,
        ]);
    }
}
