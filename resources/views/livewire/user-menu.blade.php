<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900">
  <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-center mb-6 text-gray-800 dark:text-gray-100">Mon espace</h2>
    <div class="space-y-8">
      <!-- Section Gérer mon compte -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center"><i class="fas fa-user-cog mr-2"></i>Gérer mon compte</h3>
        <div class="space-y-2">
          <a href="{{ route('profile.edit') }}" class="block w-full px-4 py-3 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition"><i class="fas fa-id-card mr-2"></i>Informations personnelles</a>
          <a href="{{ route('payments.index') }}" class="block w-full px-4 py-3 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition"><i class="fas fa-wallet mr-2"></i>Moyens de paiement</a>
          <a href="{{ route('security.settings') }}" class="block w-full px-4 py-3 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition"><i class="fas fa-shield-alt mr-2"></i>Paramètres de sécurité</a>
        </div>
      </div>

      <!-- Section Préférences -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center"><i class="fas fa-sliders-h mr-2"></i>Préférences</h3>
        <div class="space-y-2">
          <a href="{{ route('preferences.currency') }}" class="block w-full px-4 py-3 rounded-lg bg-gray-50 text-gray-800 font-semibold border border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 transition"><i class="fas fa-coins mr-2"></i>Devises</a>
          <a href="{{ route('preferences.display') }}" class="block w-full px-4 py-3 rounded-lg bg-gray-50 text-gray-800 font-semibold border border-gray-200 hover:bg-gray-100 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 transition"><i class="fas fa-moon mr-2"></i>Affichage</a>
        </div>
      </div>

      <!-- Section Aide -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center"><i class="fas fa-question-circle mr-2"></i>Aide</h3>
        <div class="space-y-2">
          <a href="#" class="block w-full px-4 py-3 rounded-lg bg-pink-50 text-pink-700 font-semibold hover:bg-pink-100 transition"><i class="fas fa-headset mr-2"></i>Contacter le service clients</a>
        </div>
      </div>

      <!-- Section Gérer mon établissement -->
      <div>
        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-200 mb-3 flex items-center"><i class="fas fa-building mr-2"></i>Gérer mon établissement</h3>
        <div class="space-y-2">
          <a href="{{ route('contact.hebergement') }}" class="block w-full px-4 py-3 rounded-lg bg-blue-50 text-blue-700 font-semibold hover:bg-blue-100 transition"><i class="fas fa-plus-circle mr-2"></i>Inscrire mon établissement</a>
          @if(Auth::user()?->properties()->exists())
          <a href="{{ route('owner.dashboard') }}" class="block w-full px-4 py-3 rounded-lg bg-green-50 text-green-700 font-semibold hover:bg-green-100 transition"><i class="fas fa-chart-line mr-2"></i>Tableau de bord propriétaire</a>
          @endif
        </div>
      </div>

      <!-- Bouton Se déconnecter -->
      <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full px-4 py-3 rounded-lg bg-gray-600 text-white font-semibold hover:bg-gray-700 transition"><i class="fas fa-sign-out-alt mr-2"></i>Se déconnecter</button>
      </form>
    </div>
  </div>
</div>