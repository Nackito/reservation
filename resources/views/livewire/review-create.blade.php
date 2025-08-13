<div class="container mx-auto p-4">
  <h2 class="mb-4 text-2xl font-bold">
    {{ $edit ? 'Modifier mon avis pour' : 'Laisser un avis pour' }} {{ $booking->property->name }}
  </h2>
  @if (session()->has('success'))
  <div class="mb-4 p-2 bg-green-100 text-green-800 rounded">{{ session('success') }}</div>
  @endif
  <form wire:submit.prevent="submit">
    <div class="mb-4">
      <label for="review" class="block text-gray-700">Votre avis</label>
      <textarea id="review" wire:model.defer="review" class="border text-black p-2 rounded w-full" required></textarea>
      @error('review') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>
    <div class="mb-4">
      <label for="rating" class="block text-gray-700">Note</label>
      <select id="rating" wire:model.defer="rating" class="border text-black p-2 rounded w-full" required>
        <option value="">Choisir une note</option>
        @for($i = 1; $i <= 5; $i++)
          <option value="{{ $i }}">{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</option>
          @endfor
      </select>
      @error('rating') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>
    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">
      {{ $edit ? 'Mettre à jour' : 'Envoyer' }}
    </button>
    <a href="{{ url()->previous() }}" class="ml-4 text-blue-500 hover:underline">Annuler</a>
  </form>
</div>