<div class=" flex flex-col items-center justify-center">
  <div class="w-full max-w-md relative">
    <a href="{{ route('user.menu') }}" class="absolute left-0 top-0 mt-4 ml-4 text-blue-600 hover:underline flex items-center gap-1 text-sm font-semibold">
      <i class="fas fa-arrow-left"></i> Retour à mon espace
    </a>
    <div class="bg-white shadow-lg p-6 mt-8">
      <p class="mb-4"> Configurer une authentification à double facteur </p>
      <div class="space-y-8">
        <!-- Section Double Authentification -->
        <div>
          <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-800 mb-3 flex items-center">
            <i class="fas fa-shield-alt mr-2"></i>Vérification à double facteur
          </h3>
          <div class="space-y-2">
            @if(Auth::user()->two_factor_secret)
            <div class="flex items-center justify-between bg-green-50 text-green-700 px-4 py-3 rounded-lg">
              <span>Double authentification activée</span>
              <form method="POST" action="{{ route('two-factor.disable') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="ml-4 px-4 py-2 rounded bg-red-500 text-white font-semibold hover:bg-red-600 transition">Désactiver</button>
              </form>
            </div>
            @else
            <form method="POST" action="{{ route('two-factor.enable') }}">
              @csrf
              <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700 transition">Activer la double authentification</button>
            </form>
            @endif
          </div>
          @if(session('status'))
          <div class="mt-4 text-center text-green-600 font-semibold">{{ session('status') }}</div>
          @endif
          @if(session('error'))
          <div class="mt-4 text-center text-red-600 font-semibold">{{ session('error') }}</div>
          @endif
        </div>
        <!-- Autres paramètres de sécurité à venir... -->
      </div>
    </div>
  </div>