<x-app-layout>
  <div class="max-w-3xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">Choisir votre devise</h1>

    @if (session('status'))
    <div class="mb-4 p-3 rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ route('preferences.currency.update') }}" class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
      @csrf
      <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Devise</label>
      <select id="currency" name="currency" class="w-full rounded border-gray-300 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-100">
        @foreach($currencies as $code => $label)
        <option value="{{ $code }}" @selected($current===$code)>{{ $label }}</option>
        @endforeach
      </select>
      @error('currency')
      <div class="text-red-600 mt-2">{{ $message }}</div>
      @enderror

      <div class="mt-4">
        <button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Enregistrer</button>
      </div>
    </form>
  </div>
</x-app-layout>