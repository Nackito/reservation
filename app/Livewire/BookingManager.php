<?php

namespace App\Livewire;

use Livewire\Component;

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
        $this->validate();

        Booking::create(['name' => $this->newBooking]);

        $this->newBooking = '';
        $this->bookings = Booking::all();

        $this->dispatchBrowserEvent('booking-added', ['message' => 'Réservation ajoutée avec succès!']);
    }

    public function render()
    {
        return view('livewire.booking-manager');
    }
}
