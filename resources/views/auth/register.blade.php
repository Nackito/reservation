@include('components.header')
@livewire('partials.navbar')

<div class="container mx-auto max-w-md py-8">
  <h2 class="text-2xl font-bold mb-6 text-center">Inscription</h2>
  <div class="mb-6 flex flex-col items-center">
    <a href="{{ url('/auth/redirect/google') }}" class="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold shadow hover:bg-gray-50 transition mb-4">
      <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
      S'inscrire avec Google
    </a>
    <span class="text-gray-400 text-xs">ou</span>
  </div>
  <form method="POST" action="{{ route('register') }}" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
    @csrf


    <div class="mb-4">
      <label for="firstname" class="block text-gray-700 text-sm font-bold mb-2">Prénom</label>
      <input id="firstname" type="text" name="firstname" value="{{ old('firstname') }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
      @error('firstname')
      <span class="text-red-500 text-xs">{{ $message }}</span>
      @enderror
    </div>

    <div class="mb-4">
      <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Nom</label>
      <input id="name" type="text" name="name" value="{{ old('name') }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
      @error('name')
      <span class="text-red-500 text-xs">{{ $message }}</span>
      @enderror
    </div>


    <div class="mb-4">
      <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
      <input id="email" type="email" name="email" value="{{ old('email') }}" required class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
      @error('email')
      <span class="text-red-500 text-xs">{{ $message }}</span>
      @enderror
    </div>

    <div class="mb-4">
      <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Mot de passe</label>
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
        S'inscrire
      </button>
    </div>
  </form>
  <p class="text-center text-gray-600 text-sm">
    Déjà inscrit ? <a href="{{ route('login') }}" class="text-blue-500 hover:text-blue-700">Connectez-vous</a>
  </p>
</div>

@include('livewire.partials.footer')
@livewireScripts