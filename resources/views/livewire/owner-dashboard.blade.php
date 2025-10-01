<div class="mx-auto max-w-7xl p-4 sm:p-6 lg:p-8">
  <h1 class="text-2xl font-bold mb-6">Tableau de bord Propriétaire</h1>

  @if($propertiesCount === 0)
  <div class="mb-6 rounded-lg border border-dashed bg-white p-6 text-center shadow-sm">
    <p class="mb-3 text-gray-700">Vous n’avez encore aucun hébergement.</p>
    <a href="{{ route('property-manager') }}" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-white hover:bg-blue-700">
      Ajouter un hébergement
    </a>
  </div>
  @endif

  <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 items-end">
    <div>
      <label for="periodSelect" class="block text-sm font-medium text-gray-700 mb-1">Période</label>
      <div class="flex flex-wrap items-center gap-2">
        <select id="periodSelect" wire:model.live="period" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="7d">7 derniers jours</option>
          <option value="30d">30 derniers jours</option>
          <option value="this_month">Ce mois</option>
          <option value="last_month">Mois dernier</option>
          <option value="all">Toutes périodes</option>
          <option value="custom">Personnalisée…</option>
        </select>
        @if($period === 'custom')
        <input type="date" wire:model.live="startDate" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
        <span class="text-gray-400">→</span>
        <input type="date" wire:model.live="endDate" class="rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
        @endif
      </div>
      <p class="mt-1 text-xs text-gray-500">Affichage: {{ $periodLabel }}</p>
    </div>

    <div>
      <label for="propertySelect" class="block text-sm font-medium text-gray-700 mb-1">Hébergement</label>
      <select id="propertySelect" wire:model.live="propertyId" class="w-full rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <option value="">Tous mes hébergements</option>
        @foreach($ownerProperties as $p)
        <option value="{{ $p->id }}">{{ $p->name ?? ('#'.$p->id) }}</option>
        @endforeach
      </select>
      @if($propertyId)
      <p class="mt-1 text-xs text-gray-500">Filtré sur l’hébergement sélectionné.</p>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-8">
    <div class="rounded-lg border bg-white p-4 shadow-sm">
      <div class="text-sm text-gray-500">Hébergements</div>
      <div class="mt-2 text-3xl font-semibold">{{ $propertiesCount }}</div>
    </div>
    <div class="rounded-lg border bg-white p-4 shadow-sm">
      <div class="text-sm text-gray-500">Réservations à venir</div>
      <div class="mt-2 text-3xl font-semibold">{{ $upcomingBookings }}</div>
    </div>
    <div class="rounded-lg border bg-white p-4 shadow-sm">
      <div class="text-sm text-gray-500">En attente d'approbation</div>
      <div class="mt-2 text-3xl font-semibold">{{ $pendingBookings }}</div>
    </div>
    <div class="rounded-lg border bg-white p-4 shadow-sm">
      <div class="text-sm text-gray-500">Revenu ce mois</div>
      <div class="mt-2 text-3xl font-semibold">{{ number_format($monthlyRevenue, 0, ',', ' ') }} FCFA</div>
    </div>
  </div>

  <div class="rounded-lg border bg-white p-4 shadow-sm">
    <div class="flex items-center justify-between">
      <h2 class="text-lg font-semibold">5 dernières réservations</h2>
    </div>
    <div class="mt-4 overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Client</th>
            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Hébergement</th>
            <th class="px-3 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Statut</th>
            <th class="px-3 py-2 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Montant</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          @forelse($latestBookings as $booking)
          <tr>
            <td class="px-3 py-2 text-sm text-gray-700">{{ $booking->created_at?->format('d/m/Y H:i') }}</td>
            <td class="px-3 py-2 text-sm text-gray-700">{{ $booking->user?->name ?? 'N/A' }}</td>
            <td class="px-3 py-2 text-sm text-gray-700">{{ $booking->property?->title ?? ('#'.$booking->property_id) }}</td>
            <td class="px-3 py-2 text-sm">
              @php
              $statusColors = [
              'pending' => 'bg-yellow-100 text-yellow-800',
              'accepted' => 'bg-blue-100 text-blue-800',
              'cancelled' => 'bg-gray-100 text-gray-800',
              ];
              $label = ucfirst($booking->status ?? 'inconnu');
              $cls = $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-800';
              @endphp
              <span class="inline-flex items-center rounded px-2 py-0.5 text-xs font-medium {{ $cls }}">{{ $label }}</span>
            </td>
            <td class="px-3 py-2 text-right text-sm text-gray-700">{{ number_format($booking->total_price ?? 0, 0, ',', ' ') }} FCFA</td>
          </tr>
          @empty
          <tr>
            <td colspan="5" class="px-3 py-6 text-center text-gray-500">Aucune réservation récente</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>