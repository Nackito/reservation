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
// Nettoyage des imports inutiles
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
// Détail des réservations par ville
use App\Http\Controllers\UserReservationsCityController;
use App\Livewire\UserCanceledReservationsCity;
// Auth Google Socialite
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

Route::get('/', HomePage::class)->name('home');
Route::get('/contact-hebergement', ContactForm::class)->name('contact.hebergement');

Route::get('/mes-reservations/avis/{booking}', App\Livewire\ReviewCreate::class)->name('user-reservations.review');

// ...existing code...
Route::get('/property-manager', PropertyManager::class)->name('property-manager');
Route::get('/booking-manager/{propertyId}', BookingManager::class)->name('booking-manager');
Route::middleware(['auth'])->group(function () {
    Route::get('chat', function () {
        return view('chat');
    })->name('user.chat');
});

// Page paramètres de sécurité utilisateur (Livewire natif)
Route::middleware(['auth'])->get('/security-settings', App\Livewire\SecuritySettings::class)->name('security.settings');




Route::middleware('auth')->group(function () {
    Route::get('/mes-reservations/annulees/ville/{city}', UserCanceledReservationsCity::class)->name('user-canceled-reservations.city');
    Route::get('/mes-reservations/ville/{city}', App\Livewire\UserReservationsCity::class)->name('user-reservations.city');
    Route::get('/reservation-details/{propertyId}', ReservationDetails::class)->name('reservations.details');
    Route::get('/user-reservations', UserReservations::class)->name('user-reservations');
    Route::get('/wishlist', App\Livewire\WishlistPage::class)->name('wishlist.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/messagerie', App\Livewire\Messaging::class)->name('messaging');
    Route::get('/mon-espace', App\Livewire\UserMenu::class)->name('user.menu');
    // Ajout d'un lien vers la messagerie dans la vue du profil
});

// Auth Google Socialite
Route::get('/auth/redirect/google', function () {
    return Socialite::driver('google')->redirect();
});

Route::get('/auth/callback/google', function () {
    $googleUser = Socialite::driver('google')->user();
    $user = User::firstOrCreate([
        'email' => $googleUser->getEmail(),
    ], [
        'name' => $googleUser->getName() ?? $googleUser->getNickname(),
        'email_verified_at' => now(),
        'password' => bcrypt(uniqid()), // mot de passe aléatoire
        'avatar' => $googleUser->getAvatar(),
    ]);
    Auth::login($user, true);
    return redirect('/');
});

// Routes pour la double authentification
Route::middleware(['auth'])->post('/two-factor/enable', function () {
    $user = Auth::user();
    // Ici tu dois générer et stocker le secret 2FA (exemple simplifié)
    $user->two_factor_secret = 'dummy-secret'; // Remplace par la vraie génération
    $user->save();
    return redirect()->route('security.settings')->with('status', 'Double authentification activée !');
})->name('two-factor.enable');

Route::middleware(['auth'])->delete('/two-factor/disable', function () {
    $user = Auth::user();
    $user->two_factor_secret = null;
    $user->save();
    return redirect()->route('security.settings')->with('status', 'Double authentification désactivée.');
})->name('two-factor.disable');

require __DIR__ . '/auth.php';
