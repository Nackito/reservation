<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use App\Models\Booking;
use App\Models\Reviews;
use Carbon\Carbon;

class ReservationDetails extends Component
{
    public $propertyId;
    public $reservations;
    public $review;
    public $rating;
    public $selectedBookingId;
    public $showReviewModal = false;
    public $canLeaveReview = false;
    public $editReviewId;
    public $editReviewContent;
    public $editReviewRating;

    public function mount($propertyId)
    {
        $this->propertyId = $propertyId;

        // Récupérer toutes les réservations pour cette propriété
        $this->reservations = Booking::with('property.images')
            ->where('property_id', $this->propertyId)
            ->where('user_id', Auth::id())
            ->where('status', 'accepted')
            ->where('end_date', '<', Carbon::now()) // Date de sortie < date actuelle
            ->get();
    }

    public function hasReview($propertyId)
    {
        return Reviews::where('user_id', Auth::id())
            ->where('property_id', $propertyId)
            ->exists();
    }

    public function openReviewModal($bookingId)
    {
        $this->selectedBookingId = $bookingId;
        $this->showReviewModal = true;
    }

    public function closeReviewModal()
    {
        $this->showReviewModal = false;
        $this->reset(['review', 'rating']);
    }

    public function submitReview()
    {
        $this->validate([
            'review' => 'required|string|max:1000',
            'rating' => 'required|integer|min:1|max:5',
        ]);

        Reviews::create([
            'user_id' => Auth::id(),
            'property_id' => Booking::find($this->selectedBookingId)->property_id,
            'review' => $this->review,
            'rating' => $this->rating,
            'approved' => false, // En attente de validation par l'admin
        ]);

        $this->reset(['review', 'rating', 'selectedBookingId']);
        $this->closeReviewModal();

        session()->flash('message', 'Votre avis a été soumis et est en attente de validation.');
    }

    public function openEditReviewModal($propertyId)
    {
        $review = Reviews::where('user_id', Auth::id())
            ->where('property_id', $propertyId)
            ->first();

        if ($review) {
            $this->editReviewId = $review->id;
            $this->editReviewContent = $review->review;
            $this->editReviewRating = $review->rating;
            $this->showReviewModal = true;
        }
    }

    public function updateReview()
    {
        $this->validate([
            'editReviewContent' => 'required|string|max:1000',
            'editReviewRating' => 'required|integer|min:1|max:5',
        ]);

        $review = Reviews::find($this->editReviewId);

        if ($review) {
            $review->update([
                'review' => $this->editReviewContent,
                'rating' => $this->editReviewRating,
            ]);

            $this->reset(['editReviewId', 'editReviewContent', 'editReviewRating', 'showReviewModal']);
            session()->flash('message', 'Votre avis a été mis à jour avec succès.');
        }
    }

    public function render()
    {
        return view('livewire.reservation-details');
    }
}
