<div class="container mx-auto py-8">
  <h1 class="text-3xl font-bold mb-6 text-gray-800 dark:text-gray-100">Ma liste de souhaits</h1>
  @if($wishlists->isEmpty())
  <div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 p-4 rounded">Vous n'avez aucun hébergement dans votre liste de souhaits.</div>
  @else
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($wishlists as $wishlist)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4 flex flex-col group cursor-pointer hover:bg-pink-50 dark:hover:bg-gray-700 transition" onclick="window.location='{{ route('booking-manager', $wishlist->property->id) }}'">
      <img src="{{ $wishlist->property->images->first() ? asset('storage/' . $wishlist->property->images->first()->image_path) : 'https://via.placeholder.com/300x200' }}" alt="Image de la propriété" class="w-full h-48 object-cover rounded mb-4 bg-gray-100 dark:bg-gray-700">
      <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-2 group-hover:text-pink-600 dark:group-hover:text-pink-400">{{ $wishlist->property->name }}</h2>
      <p class="text-gray-600 dark:text-gray-300 mb-2">{{ $wishlist->property->city }}, {{ $wishlist->property->municipality }}</p>
      <p class="text-gray-700 dark:text-gray-200 mb-4">{{ Str::limit($wishlist->property->description, 80) }}</p>
      <button wire:click.stop="removeFromWishlist({{ $wishlist->id }})" class="mt-2 px-3 py-2 bg-pink-100 dark:bg-pink-900 hover:bg-pink-200 dark:hover:bg-pink-800 text-pink-600 dark:text-pink-200 rounded shadow flex items-center gap-2 self-end" title="Retirer de la liste de souhaits">
        <i class="fas fa-heart-broken"></i> Retirer
      </button>
    </div>
    @endforeach
  </div>
  @endif
</div>