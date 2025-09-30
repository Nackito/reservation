<x-filament-panels::page>
  <form wire:submit="save" class="space-y-6">
    {{ $this->form }}

    <div class="flex items-center gap-3">
      <x-filament::button type="submit" color="primary">
        Enregistrer
      </x-filament::button>
    </div>
  </form>
</x-filament-panels::page>