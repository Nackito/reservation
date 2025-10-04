<x-app-layout>
  <div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">Préférences d'affichage</h1>

    @if (session('status'))
    <div class="mb-4 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('preferences.display.update') }}" class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4 shadow text-gray-800 dark:text-gray-100">
      @csrf
      <fieldset>
        <legend class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-2">Thème</legend>
        <div class="space-y-3">
          <label class="inline-flex items-center gap-3 px-2 py-1 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
            <input class="accent-blue-600 dark:accent-blue-400" type="radio" name="theme" value="system" @checked($theme==='system' )>
            <span class="text-gray-800 dark:text-gray-100">Automatique (système)</span>
          </label>
          <label class="inline-flex items-center gap-3 px-2 py-1 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
            <input class="accent-blue-600 dark:accent-blue-400" type="radio" name="theme" value="light" @checked($theme==='light' )>
            <span class="text-gray-800 dark:text-gray-100">Clair</span>
          </label>
          <label class="inline-flex items-center gap-3 px-2 py-1 rounded hover:bg-gray-50 dark:hover:bg-gray-700">
            <input class="accent-blue-600 dark:accent-blue-400" type="radio" name="theme" value="dark" @checked($theme==='dark' )>
            <span class="text-gray-800 dark:text-gray-100">Sombre</span>
          </label>
        </div>
      </fieldset>

      @error('theme')
      <div class="text-red-600 dark:text-red-400 mt-2">{{ $message }}</div>
      @enderror

      <div class="mt-4">
        <button class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800">Enregistrer</button>
      </div>
    </form>

    <script>
      document.addEventListener('change', (e) => {
        if (e.target && e.target.name === 'theme') {
          const val = e.target.value;
          try {
            localStorage.setItem('theme', val);
          } catch {}
          const html = document.documentElement;
          if (val === 'dark') html.classList.add('dark');
          else if (val === 'light') html.classList.remove('dark');
          else {
            // system
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            html.classList.toggle('dark', prefersDark);
          }
        }
      });
    </script>
  </div>
</x-app-layout>