<x-app-layout>
  <div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">Préférences d'affichage</h1>

    @if (session('status'))
    <div class="mb-4 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('preferences.display.update') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
      @csrf
      <fieldset>
        <legend class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Thème</legend>
        <div class="space-y-2">
          <label class="inline-flex items-center gap-2">
            <input type="radio" name="theme" value="system" @checked($theme==='system' )>
            <span>Automatique (système)</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="radio" name="theme" value="light" @checked($theme==='light' )>
            <span>Clair</span>
          </label>
          <label class="inline-flex items-center gap-2">
            <input type="radio" name="theme" value="dark" @checked($theme==='dark' )>
            <span>Sombre</span>
          </label>
        </div>
      </fieldset>

      @error('theme')
      <div class="text-red-600 mt-2">{{ $message }}</div>
      @enderror

      <div class="mt-4">
        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Enregistrer</button>
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