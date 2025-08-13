<?php

namespace App\Livewire;


use Livewire\Component;
use App\Models\Booking;
use App\Models\Reviews;
use Illuminate\Support\Facades\Auth;

class ReviewCreate extends Component
{

    public $booking;
    public $review = '';
    public $rating = '';


    public function mount($booking)
    {
        $this->booking = Booking::with('property')->findOrFail($booking);
    }

    public function submit()
    {
        $this->validate([
            'review' => 'required|string|min:3',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        Reviews::create([
            'user_id' => Auth::id(),
            'property_id' => $this->booking->property->id,
            'review' => $this->review,
            'rating' => $this->rating,
            'approved' => false,
        ]);

        session()->flash('success', 'Votre avis a bien été enregistré.');
        return redirect()->route('user-reservations');
    }

    public function render()
    {
        return view('livewire.review-create', [
            'booking' => $this->booking,
        ]);
    }
}
