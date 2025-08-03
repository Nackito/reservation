<div class="min-h-screen flex flex-col items-center justify-center bg-gray-50 dark:bg-gray-900">
  <div class="w-full max-w-sm bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    <h2 class="text-2xl font-bold text-center mb-6 text-gray-800 dark:text-gray-100">Mon espace</h2>
    <div class="space-y-4">
      <a href="{{ route('profile.edit') }}" class="block w-full px-4 py-3 rounded-lg bg-blue-600 text-white text-center font-semibold hover:bg-blue-700 transition">Profil</a>
      <a href="{{ route('messaging') }}" class="block w-full px-4 py-3 rounded-lg bg-pink-600 text-white text-center font-semibold hover:bg-pink-700 transition">Messagerie</a>
      <form id="logout-form-mobile" action="{{ route('logout') }}" method="POST">
        @csrf
        <button type="submit" class="w-full px-4 py-3 rounded-lg bg-gray-600 text-white font-semibold hover:bg-gray-700 transition">Se déconnecter</button>
      </form>
    </div>
  </div>
</div>