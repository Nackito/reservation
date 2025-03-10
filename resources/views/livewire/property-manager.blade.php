<div>
    <h1 class="text-2xl font-bold mb-4">Gestion des Propriétés</h1>

    <form wire:submit.prevent="store" class="mb-4">
        <input type="hidden" wire:model="propertyId">
        <div class="mb-4">
            <label for="name" class="block text-gray-700">Nom</label>
            <input type="text" wire:model="name" class="border p-2 rounded w-full" required>
            @error('name') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label for="description" class="block text-gray-700">Description</label>
            <textarea wire:model="description" class="border p-2 rounded w-full" required></textarea>
            @error('description') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <div class="mb-4">
            <label for="price_per_night" class="block text-gray-700">Prix par nuit</label>
            <input type="number" wire:model="price_per_night" class="border p-2 rounded w-full" required>
            @error('price_per_night') <span class="text-red-500">{{ $message }}</span> @enderror
        </div>
        <button type="submit" class="bg-primary text-white py-2 px-4 rounded">Ajouter</button>
        <button type="button" wire:click="update" class="bg-yellow-500 text-white py-2 px-4 rounded">Modifier</button>
    </form>

    <table class="min-w-full bg-white">
        <thead>
            <tr>
                <th class="py-2 px-4 border-b">Nom</th>
                <th class="py-2 px-4 border-b">Description</th>
                <th class="py-2 px-4 border-b">Prix par nuit</th>
                <th class="py-2 px-4 border-b">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($properties as $property)
            <tr>
                <td class="py-2 px-4 border-b">{{ $property->name ?? 'Nom non disponible' }}</td>
                <td class="py-2 px-4 border-b">{{ $property->description ?? 'Description non disponible'}}</td>
                <td class="py-2 px-4 border-b">{{ $property->price_per_night ?? 'Prix non disponible' }}</td>
                <td class="py-2 px-4 border-b">
                    <button wire:click="edit({{ $property->id }})" class="bg-yellow-500 text-white py-1 px-2 rounded">Modifier</button>
                    <button wire:click="delete({{ $property->id }})" class="bg-red-500 text-white py-1 px-2 rounded">Supprimer</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>