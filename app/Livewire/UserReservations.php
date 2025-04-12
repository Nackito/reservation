<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Booking;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Property;
use App\Models\Reviews;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class UserReservations extends Component
{
    public $pendingBookings;
    public $acceptedBookings;
    public $pastBookings;
    public $canceledBookings;
    public $review;
    public $activeTab = 'pending'; // Par defaut, active les réservations en attente
    public $rating;
    public $firstImage;
    public $canLeaveReview = false;
    public $selectedBookingId;
    public $ongoingBookings;
    public $showReviewModal = false;

    public function mount()
    {
        $userId = Auth::id();
        $this->rating = 0;
        // Recupérer les réservations en cours
        $this->ongoingBookings = Booking::with('property')->where('user_id', $userId)->where('status', 'accepted')->where('end_date', '>=', Carbon::now())->get();
        // Récupérer les réservations acceptées
        $this->acceptedBookings = Booking::with('property')->where('user_id', $userId)->where('status', 'accepted')->get();
        // Vérifiez si l'utilisateur peut laisser un avis
        $this->canLeaveReview = Booking::where('user_id', $userId)
            ->where('end_date', '<', Carbon::now()) // Vérifie si la date de sortie est passée
            ->exists();

        // Récupérer les réservations en attente
        $this->pendingBookings = Booking::with('property.images')
            ->where('user_id', $userId)
            ->where('end_date', '>=', Carbon::now()) // Date de sortie >= date actuelle
            ->where('status', 'pending') // Statut en attente
            ->get();

        // Récupérer les réservations passées
        $this->pastBookings = Booking::with('property.images')
            ->where('user_id', $userId)
            ->where('end_date', '<', Carbon::now()) // Date de sortie < date actuelle
            ->where('status', 'accepted') // Statut accepté
            ->get();

        // Récupérer les réservations annulées
        $this->canceledBookings = Booking::with('property.images')
            ->where('user_id', $userId)
            ->where('status', 'canceled') // Statut annulé
            ->get();
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
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

    public function deleteBooking($id)
    {
        $booking = Booking::find($id);

        if ($booking) {
            // Mettre à jour le statut de la réservation à "canceled"
            $booking->status = 'canceled';
            $booking->save();

            // Mettre à jour les listes de réservations
            $this->pendingBookings = Booking::with('property.images')
                ->where('user_id', Auth::id())
                ->where('end_date', '>=', Carbon::now())
                ->where('status', 'pending')
                ->get();

            $this->canceledBookings = Booking::with('property.images')
                ->where('user_id', Auth::id())
                ->where('status', 'canceled')
                ->get();

            session()->flash('message', 'Réservation annulée avec succès.');
        }
    }

    public function acceptBooking($id)
    {
        $booking = Booking::find($id);
        // Ajoutez ici la logique pour accepter la réservation
        $booking->status = 'accepted';
        $booking->save();
        LivewireAlert::title('Réservation acceptée avec succès!')->success()->show();
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

    public function render()
    {
        //return view('livewire.user-reservations')->extends('layouts.app')->section('content');
        return view('livewire.user-reservations');
    }
}
