<x-filament-panels::page>
  <x-slot name="header">
    <h1 class="text-xl font-semibold text-gray-900 dark:text-white">
      Conversations
    </h1>
  </x-slot>

  <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- Liste des utilisateurs -->
    <div class="lg:col-span-1 bg-white p-4 rounded-lg shadow">
      <h2 class="font-medium mb-4">Utilisateurs</h2>
      @livewire('admin-chat-box')
    </div>
    <!-- Messages et réponse -->
    <div class="lg:col-span-3 bg-white p-4 rounded-lg shadow">
      @livewire('admin-chat-box')
    </div>
  </div>
</x-filament-panels::page>