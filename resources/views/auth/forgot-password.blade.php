@include('components.header')
@livewire('partials.navbar')
<div class="container mx-auto max-w-md py-8">
  <h2 class="text-2xl font-bold mb-6 text-center">Mot de passe oublié</h2>
  @if (session('status'))
  <div class="mb-4 font-medium text-sm text-green-600">
    {{ session('status') }}
  </div>
  @endif
  <form method="POST" action="{{ route('password.email') }}" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    @csrf
    <div class="mb-4">
      <label for="email" class="block text-gray-700 text-sm font-bold mb-2">E‑mail</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
      @error('email')
      <span class="text-red-500 text-xs">{{ $message }}</span>
      @enderror
    </div>
    <div class="flex items-center justify-between">
      <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
        Envoyer le lien de réinitialisation
      </button>
    </div>
  </form>
  <p class="text-center text-gray-600 text-sm">
    <a href="{{ route('login') }}" class="text-blue-500 hover:text-blue-700">Retour à la connexion</a>
  </p>
</div>
@livewireScripts