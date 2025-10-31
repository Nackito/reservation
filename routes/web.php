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
use App\Livewire\OwnerDashboard;
use App\Http\Requests\Auth\RegisterRequest;
// Nettoyage des imports inutiles
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use PragmaRX\Google2FA\Google2FA;
// Détail des réservations par ville
use App\Http\Controllers\UserReservationsCityController;
use App\Livewire\UserCanceledReservationsCity;
// Auth Google Socialite
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Http\Controllers\PreferencesController;

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

// Route pour mettre à jour le thème utilisateur

// Route unique pour mettre à jour toutes les préférences utilisateur
Route::middleware(['auth'])->post('/user/preferences', function (\Illuminate\Http\Request $request) {
    $supported = implode(',', config('currency.supported'));
    $request->validate([
        'currency' => "required|string|in:$supported",
        'locale' => 'required|string|in:fr,en,es,de,pt',
        'theme' => 'required|string|in:light,dark,system',
    ]);
    $user = Auth::user();
    $user->currency = $request->input('currency');
    $user->locale = $request->input('locale');
    $user->theme = $request->input('theme');
    $user->save();
    return back()->with('status', 'Préférences mises à jour !');
})->name('user.preferences.update');



Route::middleware('auth')->group(function () {
    Route::get('/mes-reservations/annulees/ville/{city}', UserCanceledReservationsCity::class)->name('user-canceled-reservations.city');
    Route::get('/mes-reservations/ville/{city}', App\Livewire\UserReservationsCity::class)->name('user-reservations.city');
    Route::get('/reservation-details/{propertyId}', ReservationDetails::class)->name('reservations.details');
    Route::get('/user-reservations', UserReservations::class)->name('user-reservations');
    Route::get('/owner/dashboard', OwnerDashboard::class)->name('owner.dashboard');
    Route::get('/wishlist', App\Livewire\WishlistPage::class)->name('wishlist.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/messagerie', App\Livewire\Messaging::class)->name('messaging');
    Route::get('/mon-espace', App\Livewire\UserMenu::class)->name('user.menu');
    // Checkout de paiement pour une réservation
    Route::get('/paiement/reservation/{booking}', App\Livewire\PaymentCheckout::class)->name('payment.checkout');
    // Ajout d'un lien vers la messagerie dans la vue du profil
});

// Route pour mettre à jour la langue utilisateur


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

// Route pour mettre à jour la devise utilisateur

// Préférences: pages et updates
Route::get('/preferences/currency', [PreferencesController::class, 'currency'])->name('preferences.currency');
Route::post('/preferences/currency', [PreferencesController::class, 'updateCurrency'])->name('preferences.currency.update');
Route::get('/preferences/display', [PreferencesController::class, 'display'])->name('preferences.display');
Route::post('/preferences/display', [PreferencesController::class, 'updateDisplay'])->name('preferences.display.update');

// Page statique: Politique de confidentialité
Route::view('/privacy-policy', 'privacy')->name('privacy');


// Routes pour la double authentification
Route::middleware(['auth'])->post('/two-factor/enable', function () {
    $user = Auth::user();
    $google2fa = new Google2FA();
    $secret = $google2fa->generateSecretKey();
    $user->two_factor_secret = $secret;
    $user->save();

    // Générer l'URL du QR code
    $company = config('app.name', 'ReservationApp');
    $email = $user->email;
    $qrUrl = $google2fa->getQRCodeUrl($company, $email, $secret);

    // Générer le QR code inline avec BaconQrCode
    $qrImage = null;
    try {
        $writer = new \BaconQrCode\Renderer\ImageRenderer(
            new \BaconQrCode\Renderer\RendererStyle\RendererStyle(200),
            new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
        );
        $qrCode = new \BaconQrCode\Writer($writer);
        $qrImage = $qrCode->writeString($qrUrl);
    } catch (\Exception $e) {
        $qrImage = null;
    }

    // Passer le QR code et le secret à la vue via la session
    return redirect()->route('security.settings')
        ->with('status', 'Double authentification activée !')
        ->with('two_factor_qr', $qrImage)
        ->with('two_factor_secret', $secret);
})->name('two-factor.enable');

Route::middleware(['auth'])->delete('/two-factor/disable', function () {
    $user = Auth::user();
    $user->two_factor_secret = null;
    $user->save();
    return redirect()->route('security.settings')->with('status', 'Double authentification désactivée.');
})->name('two-factor.disable');

require_once __DIR__ . '/auth.php';

// CinetPay callbacks
Route::post('/cinetpay/notify', [\App\Http\Controllers\CinetPayController::class, 'notify'])->name('cinetpay.notify');
Route::get('/cinetpay/return', [\App\Http\Controllers\CinetPayController::class, 'return'])->name('cinetpay.return');

// Routes de simulation CinetPay (activables via config)
if (config('cinetpay.simulation_enabled')) {
    Route::middleware(['auth'])
        ->prefix('cinetpay/sim')
        ->name('cinetpay.sim.')
        ->group(function () {
            Route::post('/success', [\App\Http\Controllers\CinetPayController::class, 'simulateSuccess'])
                ->name('success');
            Route::post('/fail', [\App\Http\Controllers\CinetPayController::class, 'simulateFail'])
                ->name('fail');
            Route::post('/cancel', [\App\Http\Controllers\CinetPayController::class, 'simulateCancel'])
                ->name('cancel');
        });
}
