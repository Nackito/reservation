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
    public $edit = false;
    public $userReview = null;


    public function mount($booking)
    {
        $this->booking = Booking::with('property')->findOrFail($booking);
        $user = Auth::user();
        $this->userReview = \App\Models\Reviews::where('user_id', $user->id)
            ->where('property_id', $this->booking->property->id)
            ->first();
        $this->edit = isset($_GET['edit']) && $this->userReview;
        if ($this->edit) {
            $this->review = $this->userReview->review;
            $this->rating = $this->userReview->rating;
        }
    }

    public function submit()
    {
        $this->validate([
            'review' => 'required|string|min:3',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        if ($this->edit && $this->userReview) {
            $this->userReview->update([
                'review' => $this->review,
                'rating' => $this->rating,
            ]);
            session()->flash('success', 'Votre avis a bien été mis à jour.');
        } else {
            Reviews::create([
                'user_id' => Auth::id(),
                'property_id' => $this->booking->property->id,
                'review' => $this->review,
                'rating' => $this->rating,
                'approved' => false,
            ]);
            session()->flash('success', 'Votre avis a bien été enregistré.');
        }
        return redirect()->route('user-reservations');
    }

    public function render()
    {
        return view('livewire.review-create', [
            'booking' => $this->booking,
        ]);
    }
}
