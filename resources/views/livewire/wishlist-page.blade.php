<div class="container mx-auto py-8">
  <h1 class="text-3xl font-bold mb-6 text-gray-800">Ma liste de souhaits</h1>
  @if($wishlists->isEmpty())
  <div class="bg-yellow-100 text-yellow-800 p-4 rounded">Vous n'avez aucun hébergement dans votre liste de souhaits.</div>
  @else
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    @foreach($wishlists as $wishlist)
    <div class="bg-white rounded-lg shadow p-4 flex flex-col">
      <img src="{{ $wishlist->property->images->first() ? asset('storage/' . $wishlist->property->images->first()->image_path) : 'https://via.placeholder.com/300x200' }}" alt="Image de la propriété" class="w-full h-48 object-cover rounded mb-4">
      <h2 class="text-xl font-semibold text-gray-800 mb-2">{{ $wishlist->property->name }}</h2>
      <p class="text-gray-600 mb-2">{{ $wishlist->property->city }}, {{ $wishlist->property->municipality }}</p>
      <p class="text-gray-700 mb-4">{{ Str::limit($wishlist->property->description, 80) }}</p>
    </div>
    @endforeach
  </div>
  @endif
</div>