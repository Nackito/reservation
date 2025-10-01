<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class OwnerDashboard extends Component
{
  public int $propertiesCount = 0;
  public int $upcomingBookings = 0;
  public int $pendingBookings = 0;
  public float $monthlyRevenue = 0.0;

  #[Layout('layouts.app')]
  public function render()
  {
    $user = Auth::user();
    if (!$user) {
      abort(403);
    }

    $ownerPropertyIds = Property::query()
      ->where('user_id', $user->id)
      ->pluck('id');

    $this->propertiesCount = $ownerPropertyIds->count();

    $today = Carbon::today();

    $this->upcomingBookings = Booking::query()
      ->whereIn('property_id', $ownerPropertyIds)
      ->whereDate('start_date', '>=', $today)
      ->count();

    $this->pendingBookings = Booking::query()
      ->whereIn('property_id', $ownerPropertyIds)
      ->where('status', 'pending')
      ->count();

    $monthStart = $today->copy()->startOfMonth();
    $monthEnd = $today->copy()->endOfMonth();

    $this->monthlyRevenue = (float) Booking::query()
      ->whereIn('property_id', $ownerPropertyIds)
      ->where('payment_status', 'paid')
      ->whereBetween('paid_at', [$monthStart, $monthEnd])
      ->sum('total_price');

    $latest = Booking::with(['user', 'property'])
      ->whereIn('property_id', $ownerPropertyIds)
      ->latest()
      ->take(5)
      ->get();

    return view('livewire.owner-dashboard', [
      'latestBookings' => $latest,
    ]);
  }
}
