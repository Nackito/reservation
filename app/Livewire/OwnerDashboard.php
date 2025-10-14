<?php

namespace App\Livewire;

use App\Models\Booking;
use App\Models\Property;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

class OwnerDashboard extends Component
{
  public int $propertiesCount = 0;
  public int $upcomingBookings = 0;
  public int $pendingBookings = 0;
  public float $monthlyRevenue = 0.0;
  public string $period = 'this_month'; // 7d, 30d, this_month, last_month, all, custom
  public ?string $startDate = null; // format Y-m-d (pour custom)
  public ?string $endDate = null;   // format Y-m-d (pour custom)
  public ?int $propertyId = null;   // filtre hébergement

  #[Layout('layouts.app')]
  public function render()
  {
    $user = Auth::user();
    if (!$user) {
      abort(403);
    }

    $ownerProperties = Property::query()
      ->where('user_id', $user->id)
      ->select('id', 'name')
      ->orderBy('name')
      ->get();

    $ownerPropertyIds = $ownerProperties->pluck('id');

    // Si un hébergement est sélectionné, vérifier qu'il appartient au user
    if ($this->propertyId && !$ownerPropertyIds->contains($this->propertyId)) {
      $this->propertyId = null;
    }

    $filteredPropertyIds = $this->propertyId ? collect([$this->propertyId]) : $ownerPropertyIds;

    $this->propertiesCount = $ownerPropertyIds->count();

    $today = Carbon::today();

    $this->upcomingBookings = Booking::query()
      ->whereIn('property_id', $filteredPropertyIds)
      ->whereDate('start_date', '>=', $today)
      ->count();

    $this->pendingBookings = Booking::query()
      ->whereIn('property_id', $filteredPropertyIds)
      ->where('status', 'pending')
      ->count();

    [$rangeStart, $rangeEnd] = $this->dateRange();

    $hasPaymentStatus = Schema::hasColumn('bookings', 'payment_status');
    $hasPaidAt = Schema::hasColumn('bookings', 'paid_at');

    $this->monthlyRevenue = (float) Booking::query()
      ->whereIn('property_id', $filteredPropertyIds)
      ->when($hasPaymentStatus, fn($q) => $q->where('payment_status', 'paid'))
      ->when(($rangeStart && $rangeEnd) && $hasPaidAt, function ($q) use ($rangeStart, $rangeEnd) {
        $q->whereBetween('paid_at', [$rangeStart, $rangeEnd]);
      })
      ->sum('total_price');

    // Préparer devise utilisateur et taux de conversion (base XOF -> devise utilisateur)
    $userCurrency = $user && $user->currency ? $user->currency : 'XOF';
    $displayCurrency = 'XOF';
    $rate = 1.0;
    if ($userCurrency !== 'XOF') {
      try {
        $rateSrv = app(\App\Livewire\BookingManager::class)->getExchangeRate('XOF', $userCurrency);
        if (is_numeric($rateSrv) && (float) $rateSrv > 0) {
          $rate = (float) $rateSrv;
          $displayCurrency = $userCurrency;
        }
      } catch (\Throwable $e) {
        // fallback XOF
      }
    }

    $monthlyRevenueDisplay = round($this->monthlyRevenue * $rate, 2);

    $latest = Booking::with(['user', 'property'])
      ->whereIn('property_id', $filteredPropertyIds)
      ->when($rangeStart && $rangeEnd, function ($q) use ($rangeStart, $rangeEnd) {
        $q->whereBetween('created_at', [$rangeStart, $rangeEnd]);
      })
      ->latest()
      ->take(5)
      ->get();

    return view('livewire.owner-dashboard', [
      'latestBookings' => $latest,
      'periodLabel' => $this->periodLabel(),
      'rangeStart' => $rangeStart,
      'rangeEnd' => $rangeEnd,
      'ownerProperties' => $ownerProperties,
      'rate' => $rate,
      'displayCurrency' => $displayCurrency,
      'monthlyRevenueDisplay' => $monthlyRevenueDisplay,
    ]);
  }

  protected function dateRange(): array
  {
    $today = Carbon::today();
    return match ($this->period) {
      '7d' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
      '30d' => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
      'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
      'last_month' => [
        $today->copy()->subMonth()->startOfMonth(),
        $today->copy()->subMonth()->endOfMonth(),
      ],
      'all' => [null, null],
      'custom' => $this->customRange(),
      default => [$today->copy()->startOfMonth(), $today->copy()->endOfMonth()],
    };
  }

  protected function customRange(): array
  {
    if (!$this->startDate || !$this->endDate) {
      // fallback 30 derniers jours
      $today = Carbon::today();
      return [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()];
    }
    try {
      $start = Carbon::parse($this->startDate)->startOfDay();
      $end = Carbon::parse($this->endDate)->endOfDay();
      if ($end->lt($start)) {
        [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
      }
      return [$start, $end];
    } catch (\Throwable $e) {
      $today = Carbon::today();
      return [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()];
    }
  }

  public function updatedPeriod(string $value): void
  {
    if ($value !== 'custom') {
      $this->startDate = null;
      $this->endDate = null;
    }
  }

  protected function periodLabel(): string
  {
    return match ($this->period) {
      '7d' => '7 derniers jours',
      '30d' => '30 derniers jours',
      'this_month' => 'ce mois',
      'last_month' => 'mois dernier',
      'all' => 'toutes périodes',
      'custom' => 'période personnalisée',
      default => 'ce mois',
    };
  }
}
