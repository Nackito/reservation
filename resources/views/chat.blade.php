<x-app-layout>
  <x-slot name="header">
    <h2 class="text-2xl font-semibold text-gray-800 leading-tight">
      Chat avec l'administrateur
    </h2>
  </x-slot>

  <div class="container mx-auto mt-8">
    @livewire('chat-box')
  </div>
</x-app-layout>