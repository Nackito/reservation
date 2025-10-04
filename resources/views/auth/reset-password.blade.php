@include('components.header')
@livewire('partials.navbar')
<div class="container mx-auto max-w-md py-8">
  <h2 class="text-2xl font-bold mb-6 text-center">Réinitialiser le mot de passe</h2>
  <form method="POST" action="{{ route('password.store') }}" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    @csrf
    <input type="hidden" name="token" value="{{ request()->route('token') }}">
    <div class="mb-4">
      <label for="email" class="block text-gray-700 text-sm font-bold mb-2">E‑mail</label>
      <input id="email" type="email" name="email" value="{{ old('email', request('email')) }}" required autofocus class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
      @error('email')
      <span class="text-red-500 text-xs">{{ $message }}</span>
      @enderror
    </div>
    <div class="mb-4">
      <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Nouveau mot de passe</label>
      <input id="password" type="password" name="password" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
      @error('password')
      <span class="text-red-500 text-xs">{{ $message }}</span>
      @enderror
    </div>
    <div class="mb-6">
      <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">Confirmer le mot de passe</label>
      <input id="password_confirmation" type="password" name="password_confirmation" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
    </div>
    <div class="flex items-center justify-between">
      <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
        Réinitialiser le mot de passe
      </button>
    </div>
  </form>
  <p class="text-center text-gray-600 text-sm">
    <a href="{{ route('login') }}" class="text-blue-500 hover:text-blue-700">Retour à la connexion</a>
  </p>
</div>
@livewireScripts