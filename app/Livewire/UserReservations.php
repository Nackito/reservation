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
    public $openedCity = null;
    public $cityResidences = [];

    public function toggleCity($city)
    {
        if ($this->openedCity === $city) {
            $this->openedCity = null;
            return;
        }
        $this->openedCity = $city;
        // Récupérer les résidences et le nombre de réservations pour cette ville
        $userId = Auth::id();
        $bookings = Booking::with('property.images')
            ->where('user_id', $userId)
            ->whereHas('property', function ($q) use ($city) {
                $q->where('city', $city);
            })
            ->where('status', 'accepted')
            ->where('end_date', '>', now())
            ->get();
        $this->cityResidences = $bookings->groupBy('property_id')->map(function ($bookings) {
            return [
                'property' => $bookings->first()->property,
                'count' => $bookings->count(),
            ];
        })->values();
    }
    public $pendingBookings;
    public $acceptedBookings;
    public $pastBookings;
    public $canceledBookings;
    public $review;
    public $activeTab = 'past'; // Par defaut, active les réservations passées
    public $rating;
    public $firstImage;
    public $canLeaveReview = false;
    public $selectedBookingId;
    public $ongoingBookings;
    public $showReviewModal = false;
    public $editReviewId;
    public $editReviewContent;
    public $editReviewRating;
    public $groupedPastBookings;

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


        // Récupérer les réservations passées (acceptées et end_date < aujourd'hui)
        $pastBookings = Booking::with('property.images')
            ->where('user_id', $userId)
            ->where('status', 'accepted')
            ->where('end_date', '<', Carbon::now())
            ->get();


        // Grouper les réservations par ville
        $this->groupedPastBookings = $pastBookings->groupBy(function ($booking) {
            return $booking->property->city ?? 'Ville inconnue';
        })->map(function ($bookings, $city) {
            // Dernière réservation (par date de création)
            $lastBooking = $bookings->sortByDesc('created_at')->first();
            $lastProperty = $lastBooking->property;
            $lastImage = $lastProperty->images->isNotEmpty() ? $lastProperty->images->last() : null;
            return [
                'city' => $city,
                'count' => $bookings->pluck('property_id')->unique()->count(),
                'image' => $lastImage,
            ];
        });

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

    public function hasReview($propertyId)
    {
        return Reviews::where('user_id', Auth::id())
            ->where('property_id', $propertyId)
            ->exists();
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
}
