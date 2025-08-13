<?php

// Route pour laisser un avis sur une réservation
use App\Http\Controllers\ReviewController;
// Page de détail des réservations annulées par ville
use App\Http\Controllers\UserCanceledReservationsCityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\HomeController;
use App\Http\Requests\Auth\LoginRequest;
use App\Livewire\UserReservations;
use App\Livewire\PropertyManager;
use App\Livewire\BookingManager;
use App\Livewire\HomePage;
use App\Livewire\ReservationDetails;
use App\Livewire\ContactForm;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\Route;
use PharIo\Manifest\Author;
// Détail des réservations par ville
use App\Http\Controllers\UserReservationsCityController;
use App\Livewire\UserCanceledReservationsCity;

Route::get('/', HomePage::class)->name('home');
Route::get('/contact-hebergement', ContactForm::class)->name('contact.hebergement');

Route::get('/mes-reservations/avis/{booking}', App\Livewire\ReviewCreate::class)->name('user-reservations.review');

// ...existing code...
Route::get('/mes-reservations/annulees/ville/{city}', UserCanceledReservationsCity::class)->name('user-canceled-reservations.city');
Route::get('/mes-reservations/ville/{city}', App\Livewire\UserReservationsCity::class)->name('user-reservations.city');
Route::get('/property-manager', PropertyManager::class)->name('property-manager');
Route::get('/booking-manager/{propertyId}', BookingManager::class)->name('booking-manager');
Route::get('/user-reservations', UserReservations::class)->name('user-reservations');
Route::get('/reservation-details/{propertyId}', ReservationDetails::class)->name('reservations.details');
Route::middleware(['auth'])->group(function () {
    Route::get('chat', function () {
        return view('chat');
    })->name('user.chat');
});




Route::middleware('auth')->group(function () {
    Route::get('/wishlist', App\Livewire\WishlistPage::class)->name('wishlist.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/messagerie', App\Livewire\Messaging::class)->name('messaging');
    Route::get('/mon-espace', App\Livewire\UserMenu::class)->name('user.menu');
    // Ajout d'un lien vers la messagerie dans la vue du profil
});

require __DIR__ . '/auth.php';
