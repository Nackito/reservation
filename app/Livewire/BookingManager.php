<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Livewire;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;

class BookingManager extends Component
{
    public $bookings;
    public $newBooking = '';

    protected $rules = [
        'newBooking' => 'required|string|max:255',
    ];

    public function mount()
    {
        $this->bookings = Booking::all();
    }

    public function addBooking()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $this->validate();

        Booking::create(['name' => $this->newBooking]);

        $this->newBooking = '';
        $this->bookings = Booking::all();

        $this->dispatchBrowserEvent('booking-added', ['message' => 'Réservation ajoutée avec succès!']);
    }

    public function render()
    {
        return view('livewire.booking-manager')->extends('layouts.app');
    }
}
