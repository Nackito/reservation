<div>
    <h1 class="text-2xl font-bold mb-4">Gestion des Propriétés</h1>

    <form wire:submit.prevent="store" class="mb-4">
        <input type="hidden" wire:model="propertyId">
        <h2 class="text-xl font-bold mb-4">Ajouter une propriété</h2>
        <div class="mb-4">
            <label for="name" class="block text-gray-700">Nom</label>
            <input type="text" wire:model="name" class="border text-black p-2 rounded w-full" required>
            @error('name') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700">Description</label>
            <textarea wire:model="description" class="border text-black p-2 rounded w-full" required></textarea>
            @error('description') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label for="price_per_night" class="block text-gray-700">Prix par nuit</label>
            <input type="number" wire:model="price_per_night" class="border text-black p-2 rounded w-full" required>
            @error('price_per_night') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label text-gray-700">Images</label>
            <input type="file" wire:model="images" multiple class="border p-2 rounded w-full">
            @error('images.*') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="bg-primary text-white py-2 px-4 rounded">Ajouter</button>
        <button type="button" wire:click="update" class="bg-yellow-500 text-white py-2 px-4 rounded">Modifier</button>
    </form>

    <h2 class="text-xl font-bold mb-4">Liste des propriétés</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($properties as $property)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            @if($property->image)
            <img src="{{ asset('storage/' . $property->image) }}" alt="{{ $property->name }}" class="w-full h-48 object-cover">
            @endif
            <div class="p-4">
                <h3 class="text-lg text-gray-800">{{ $property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">{{ $property->description ?? 'Description non disponible' }}</p>
                <p class="text-gray-600">{{ $property->price_per_night ?? 'Prix non disponible' }} € par nuit</p>
                <div class="mt-4 flex justify-between">
                    <button wire:click="edit({{ $property->id }})" class="bg-yellow-500 text-white py-1 px-2 rounded">Modifier</button>
                    <button wire:click="delete({{ $property->id }})" class="bg-red-500 text-white py-1 px-2 rounded">Supprimer</button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <h2 class="text-xl font-bold mb-4">Demandes de Réservation</h2>
    <ul>
        @foreach($bookings as $booking)
        <li class="border-b py-2">{{ $booking->name }}</li>
        @endforeach
    </ul>
</div>