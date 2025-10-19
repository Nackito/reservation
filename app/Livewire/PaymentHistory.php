<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class PaymentHistory extends Component
{
  public $payments;

  public function mount()
  {
    $userId = Auth::id();
    $this->payments = Payment::with(['booking.property'])
      ->whereHas('booking', function ($q) use ($userId) {
        $q->where('user_id', $userId);
      })
      ->latest()
      ->get();
  }

  public function render()
  {
    return view('livewire.payment-history');
  }
}
