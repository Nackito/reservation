<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\HomeController;
//use App\Models\Property;
//use App\Models\PropertyImage;
use App\Livewire\PropertyManager;
use App\Livewire\BookingManager;
//use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

/*Route::get('/', function () {
    return view('welcome');
})->name('home');*/

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

/*Route::get('/properties', function () {
    return view('properties.index');
})->name('properties.index');*/

Route::middleware(['auth'])->group(function () {
    Route::get('/properties', PropertyManager::class)->name('properties.index');
    Route::get('/booking-manager/{propertyId}', BookingManager::class)->name('booking-manager');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/booking-manager/{propertyId}', BookingManager::class)->name('booking-manager');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
