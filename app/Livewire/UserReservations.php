<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Booking;
use App\Models\User;
use App\Models\Property;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class UserReservations extends Component
{
    public $pendingBookings;
    public $acceptedBookings;

    public function mount()
    {
        $userId = Auth::id();
        $this->pendingBookings = Booking::with('property')->where('user_id', $userId)->where('status', 'pending')->get();
        $this->acceptedBookings = Booking::with('property')->where('user_id', $userId)->where('status', 'accepted')->get();
    }

    public function deleteBooking($id)
    {
        Booking::find($id)->delete();
        $this->pendingBookings = Booking::with('property')->where('user_id', Auth::id())->where('status', 'pending')->get();
        $this->acceptedBookings = Booking::with('property')->where('user_id', Auth::id())->where('status', 'accepted')->get();
        LivewireAlert::title('Réservation annulée avec succès!')->success()->show();
    }

    public function acceptBooking($id)
    {
        $booking = Booking::find($id);
        // Ajoutez ici la logique pour accepter la réservation
        $booking->status = 'accepted';
        $booking->save();
        LivewireAlert::title('Réservation acceptée avec succès!')->success()->show();
    }

    public function render()
    {
        //return view('livewire.user-reservations')->extends('layouts.app')->section('content');
        return view('livewire.user-reservations');
    }
}
