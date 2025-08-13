<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Property;

class ReviewController extends Controller
{
    public function create($bookingId)
    {
        $booking = Booking::with('property')->findOrFail($bookingId);
        return view('livewire.review-create', [
            'booking' => $booking,
        ]);
    }
}
