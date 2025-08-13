<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

class UserCanceledReservationsCityController extends Controller
{
  public function show($city)
  {
    $user = Auth::user();
    $decodedCity = urldecode($city);
    $canceled = Booking::with('property')
      ->whereHas('property', function ($q) use ($decodedCity) {
        $q->where('city', $decodedCity);
      })
      ->where('user_id', $user->id)
      ->where('status', 'cancelled')
      ->orderByDesc('start_date')
      ->get();

    return view('livewire.user-canceled-reservations-city', [
      'city' => $decodedCity,
      'canceled' => $canceled,
    ]);
  }
}
