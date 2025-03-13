<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Property;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class BookingManager extends Component
{
    public $propertyId;
    public $checkInDate;
    public $checkOutDate;
    public $totalPrice;
    public $bookings;

    protected $rules = [
        'checkInDate' => 'required|date',
        'checkOutDate' => 'required|date|after:checkInDate',
    ];

    public function mount($propertyId)
    {
        $this->propertyId = $propertyId;
        $this->bookings = Booking::where('property_id', $propertyId)->get();
    }

    public function calculateTotalPrice()
    {
        $property = Property::find($this->propertyId);
        $checkIn = strtotime($this->checkInDate);
        $checkOut = strtotime($this->checkOutDate);
        $days = ($checkOut - $checkIn) / 86400; // 86400 seconds in a day
        $this->totalPrice = $days * $property->price_per_night;
    }

    public function addBooking()
    {
        $this->validate();

        Booking::create([
            'property_id' => $this->propertyId,
            'user_id' => Auth::id(),
            'start_date' => $this->checkInDate,
            'end_date' => $this->checkOutDate,
            'total_price' => $this->totalPrice,
        ]);

        $this->bookings = Booking::where('property_id', $this->propertyId)->get();
        LivewireAlert::title('Réservation ajoutée avec succès!')->success()->show();
        // Redirection après l'alerte
        return redirect()->route('home');
    }

    public function render()
    {
        return view('livewire.booking-manager')->extends('layouts.app');
    }
}
