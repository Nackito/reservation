<div class="container mx-auto p-4 bg-white dark:bg-gray-900 min-h-screen">
  <h2 class="mb-4 text-3xl font-extrabold tracking-tight text-gray-900 dark:text-gray-100">Mes paiements</h2>

  @if ($payments->isEmpty())
  <div class="px-6 py-12 sm:px-12 sm:py-16">
    <div class="flex items-center gap-6">
      <div class="shrink-0 w-28 h-28 sm:w-36 sm:h-36 rounded-full overflow-hidden bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-14 h-14 text-gray-400 dark:text-gray-500">
          <path fill-rule="evenodd" d="M2.25 6.75A2.25 2.25 0 014.5 4.5h15a2.25 2.25 0 012.25 2.25v10.5A2.25 2.25 0 0119.5 19.5h-15a2.25 2.25 0 01-2.25-2.25V6.75zM3.75 9h16.5v8.25a.75.75 0 01-.75.75h-15a.75.75 0 01-.75-.75V9zm10.5-3H9.75a.75.75 0 000 1.5h4.5a.75.75 0 000-1.5z" clip-rule="evenodd" />
        </svg>
      </div>
      <div class="min-w-0">
        <div class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-gray-100">Aucun paiement pour le moment</div>
        <div class="mt-1 text-sm text-gray-500 dark:text-gray-400">Quand vous effectuerez un paiement, il apparaîtra ici.</div>
      </div>
    </div>
  </div>
  @else
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    @foreach ($payments as $p)
    @php
    $bk = $p->booking;
    $prop = $bk?->property;
    $isOk = $p->status === 'paid' || $p->status === 'successful';
    $isPending = in_array($p->status, ['pending','initialized','processing']);
    $isFailed = in_array($p->status, ['failed','canceled','cancelled','error']);
    $badge = $isOk ? 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-200 dark:border-green-800' : ($isPending ? 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-200 dark:border-yellow-800' : 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-200 dark:border-red-800');
    $label = $isOk ? 'Réussi' : ($isPending ? 'En cours' : 'Échec');
    @endphp
    <div class="flex bg-white dark:bg-gray-800 rounded-lg overflow-hidden mb-2 max-w-sm transition-shadow duration-200 hover:shadow-md border border-gray-200 dark:border-gray-700">
      <div class="flex-shrink-0 w-24 h-24">
        @if($prop && $prop->images && $prop->images->isNotEmpty())
        <img src="{{ asset('storage/' . $prop->images->first()->image_path) }}" alt="Propriété" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
        @else
        <img src="{{ asset('images/default-property.jpg') }}" alt="Par défaut" class="object-cover w-full h-full rounded-lg bg-gray-100 dark:bg-gray-700">
        @endif
      </div>
      <div class="flex flex-col justify-between p-3 flex-1 min-w-0">
        <div class="flex items-center gap-2 flex-wrap">
          <h5 class="text-base font-bold text-gray-800 dark:text-gray-100 truncate">{{ $prop->name ?? 'Réservation' }}</h5>
          <span class="inline-block text-[11px] px-2 py-0.5 rounded border {{ $badge }}">{{ $label }}</span>
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-300">
          <span class="font-medium">Txn:</span> {{ $p->transaction_id ?? '—' }}
        </div>
        <div class="text-xs text-gray-600 dark:text-gray-300">
          <span class="font-medium">Date:</span> {{ optional($p->created_at)->format('d/m/Y H:i') }}
        </div>
        @if(!is_null($bk?->total_price))
        <div class="text-xs text-gray-600 dark:text-gray-300">
          <span class="font-medium">Montant:</span> {{ number_format($bk->total_price, 2) }} XOF
        </div>
        @endif
        @if($bk)
        <div class="mt-2">
          <a href="{{ route('payment.checkout', $bk) }}" class="text-blue-600 hover:text-blue-700 text-xs underline">Voir la réservation / Payer</a>
        </div>
        @endif
      </div>
    </div>
    @endforeach
  </div>
  @endif
</div>