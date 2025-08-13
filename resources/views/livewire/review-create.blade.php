@include('components.header')
@livewire('partials.navbar')
<div class="container mx-auto p-4">
  <h2 class="mb-4 text-2xl font-bold">Laisser un avis pour {{ $booking->property->name }}</h2>
  <form action="#" method="POST">
    @csrf
    <div class="mb-4">
      <label for="review" class="block text-gray-700">Votre avis</label>
      <textarea id="review" name="review" class="border text-black p-2 rounded w-full" required></textarea>
    </div>
    <div class="mb-4">
      <label for="rating" class="block text-gray-700">Note</label>
      <select id="rating" name="rating" class="border text-black p-2 rounded w-full" required>
        <option value="">Choisir une note</option>
        @for($i = 1; $i <= 5; $i++)
          <option value="{{ $i }}">{{ $i }} étoile{{ $i > 1 ? 's' : '' }}</option>
          @endfor
      </select>
    </div>
    <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded">Envoyer</button>
    <a href="{{ url()->previous() }}" class="ml-4 text-blue-500 hover:underline">Annuler</a>
  </form>
</div>
@livewire('partials.footer')