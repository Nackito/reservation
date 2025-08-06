<?php

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

Route::get('/', HomePage::class)->name('home');
Route::get('/contact-hebergement', ContactForm::class)->name('contact.hebergement');

Route::get('/property-manager', PropertyManager::class)->name('property-manager');
Route::get('/booking-manager/{propertyId}', BookingManager::class)->name('booking-manager');
Route::get('/user-reservations', UserReservations::class)->name('user-reservations');
Route::get('/reservation-details/{propertyId}', ReservationDetails::class)->name('reservations.details');
//Route::get('/dashboard', function () {
//    return redirect('/admin');
//})->name('dashboard');
// Chat utilisateur <-> admin
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
