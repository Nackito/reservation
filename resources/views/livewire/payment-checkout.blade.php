<div class="max-w-3xl mx-auto p-4 sm:p-6">
  <h1 class="text-xl sm:text-2xl font-semibold text-gray-900 dark:text-gray-100">Paiement de votre réservation</h1>
  @php $b = $booking; @endphp
  <div class="mt-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4">
    <div class="flex items-start gap-4">
      <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 shrink-0">
        @if($b && $b->property && $b->property->images && $b->property->images->isNotEmpty())
        <img src="{{ asset('storage/' . $b->property->images->first()->image_path) }}" class="w-full h-full object-cover" alt="" />
        @endif
      </div>
      <div class="min-w-0 flex-1">
        <div class="text-base font-medium text-gray-900 dark:text-gray-100">{{ $b->property->name ?? 'Réservation' }}</div>
        <div class="text-sm text-gray-600 dark:text-gray-300 mt-1">
          @php
          try {
          $ciFr = \Illuminate\Support\Str::title(\Carbon\Carbon::parse($b->start_date)->locale('fr')->translatedFormat('l d F Y'));
          $coFr = \Illuminate\Support\Str::title(\Carbon\Carbon::parse($b->end_date)->locale('fr')->translatedFormat('l d F Y'));
          } catch (\Throwable $e) { $ciFr = $b->start_date; $coFr = $b->end_date; }
          @endphp
          <span class="font-medium">Séjour:</span> {{ $ciFr }} → {{ $coFr }}
          @if(!is_null($amount))
          @php
          $user = auth()->user();
          $baseCurrency = config('cinetpay.currency', 'XOF');
          $userCurrency = $user && $user->currency ? strtoupper($user->currency) : $baseCurrency;
          $rate = app('App\\Livewire\\BookingManager')->getExchangeRate($baseCurrency, $userCurrency);
          $showConv = $rate && $userCurrency !== $baseCurrency;
          $converted = $showConv ? round($amount * $rate, 2) : null;
          @endphp
          <span class="ml-3 font-medium">Total:</span> {{ number_format($amount, 0, ',', ' ') }} {{ $baseCurrency }}
          @if($showConv)
          <span class="text-gray-500 dark:text-gray-400">(≈ {{ number_format($converted, 2, ',', ' ') }} {{ $userCurrency }})</span>
          @endif
          @endif
        </div>
      </div>
    </div>
  </div>

  @if (session('error'))
  <div class="mt-4 p-3 rounded bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200">{{ session('error') }}</div>
  @endif

  <div class="mt-6">
    <h2 class="text-sm uppercase tracking-wide text-gray-500 dark:text-gray-400">Moyens de paiement</h2>
    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
      <button wire:click="payWithCinetPay" type="button" class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 hover:bg-gray-50">
        <span>Mobile Money / Wave (Côte d'Ivoire, Sénégal)</span>
      </button>
      <button wire:click="payWithCinetPayCard" type="button" class="inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg border border-emerald-300 dark:border-emerald-700 bg-white dark:bg-gray-800 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-900/20">
        <span>Carte bancaire (VISA / MasterCard)</span>
      </button>
    </div>
  </div>
</div>