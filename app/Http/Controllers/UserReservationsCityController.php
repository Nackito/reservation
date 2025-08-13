<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;
use App\Models\Property;

class UserReservationsCityController extends Controller
{
  public function show($city)
  {
    $user = Auth::user();
    $decodedCity = urldecode($city);
    $residences = Booking::with('property')
      ->whereHas('property', function ($q) use ($decodedCity) {
        $q->where('city', $decodedCity);
      })
      ->where('user_id', $user->id)
      ->whereIn('status', ['past', 'accepted'])
      ->orderByDesc('start_date')
      ->get();

    return view('livewire.user-reservations-city', [
      'city' => $decodedCity,
      'residences' => $residences,
    ]);
  }
}
