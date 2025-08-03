<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900">
  <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-center mb-6 text-gray-800 dark:text-gray-100">Menu principal</h2>
    <div class="space-y-4">
      <a href="/" class="block w-full px-4 py-3 rounded-lg bg-blue-50 text-blue-700 text-center font-semibold hover:bg-blue-100 transition"><i class="fas fa-home mr-2"></i>Accueil</a>
      <a href="{{ route('contact.hebergement') }}" class="block w-full px-4 py-3 rounded-lg bg-blue-50 text-blue-700 text-center font-semibold hover:bg-blue-100 transition"><i class="fas fa-plus-circle mr-2"></i>Proposer un hébergement</a>
      <a href="{{ route('user-reservations') }}" class="block w-full px-4 py-3 rounded-lg bg-gray-50 text-gray-800 text-center font-semibold hover:bg-gray-100 transition"><i class="fas fa-calendar-alt mr-2"></i>Mes réservations</a>
      <a href="{{ route('wishlist.index') }}" class="block w-full px-4 py-3 rounded-lg bg-pink-50 text-pink-700 text-center font-semibold hover:bg-pink-100 transition"><i class="fas fa-heart mr-2"></i>Mes souhaits</a>
      <a href="{{ route('profile.edit') }}" class="block w-full px-4 py-3 rounded-lg bg-blue-600 text-white text-center font-semibold hover:bg-blue-700 transition"><i class="fas fa-user mr-2"></i>Profil</a>
      <a href="{{ route('messaging') }}" class="block w-full px-4 py-3 rounded-lg bg-pink-600 text-white text-center font-semibold hover:bg-pink-700 transition"><i class="fas fa-envelope mr-2"></i>Messagerie</a>
      <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full px-4 py-3 rounded-lg bg-gray-600 text-white font-semibold hover:bg-gray-700 transition"><i class="fas fa-sign-out-alt mr-2"></i>Se déconnecter</button>
      </form>
    </div>
  </div>
</div>