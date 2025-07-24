<div class="min-h-screen bg-gray-50 py-12">
  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- En-tête --}}
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">Proposer votre hébergement</h1>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        Vous possédez un établissement en Côte d'Ivoire ? Rejoignez Afridays et faites découvrir votre hébergement à nos voyageurs.
        Remplissez ce formulaire et notre équipe vous contactera rapidement.
      </p>
    </div>

    {{-- Formulaire --}}
    <div class="bg-white rounded-lg shadow-lg p-8">
      <form wire:submit.prevent="envoyer" class="space-y-8">

        {{-- Section Informations personnelles --}}
        <div>
          <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-user mr-3 text-blue-600"></i>
            Vos informations personnelles
          </h2>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label for="prenom" class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
              <input type="text" id="prenom" wire:model="prenom"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Votre prénom">
              @error('prenom') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
              <input type="text" id="nom" wire:model="nom"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Votre nom">
              @error('nom') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
              <input type="email" id="email" wire:model="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="votre@email.com">
              @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
              <input type="tel" id="telephone" wire:model="telephone"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="+225 XX XX XX XX XX">
              @error('telephone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
          </div>
        </div>

        {{-- Section Informations de l'établissement --}}
        <div>
          <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-building mr-3 text-blue-600"></i>
            Informations de votre établissement
          </h2>

          <div class="space-y-6">
            <div>
              <label for="nom_etablissement" class="block text-sm font-medium text-gray-700 mb-2">Nom de l'établissement *</label>
              <input type="text" id="nom_etablissement" wire:model="nom_etablissement"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Nom de votre hôtel, maison, etc.">
              @error('nom_etablissement') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="type_hebergement" class="block text-sm font-medium text-gray-700 mb-2">Type d'hébergement *</label>
              <select id="type_hebergement" wire:model="type_hebergement"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                <option value="">Sélectionnez le type</option>
                @foreach($types_hebergement as $key => $type)
                <option value="{{ $key }}">{{ $type }}</option>
                @endforeach
              </select>
              @error('type_hebergement') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label for="ville" class="block text-sm font-medium text-gray-700 mb-2">Ville *</label>
                <input type="text" id="ville" wire:model="ville"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Abidjan, Bouaké, etc.">
                @error('ville') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
              </div>

              <div>
                <label for="quartier" class="block text-sm font-medium text-gray-700 mb-2">Quartier</label>
                <input type="text" id="quartier" wire:model="quartier"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Nom du quartier">
                @error('quartier') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
              </div>
            </div>

            <div>
              <label for="adresse" class="block text-sm font-medium text-gray-700 mb-2">Adresse complète *</label>
              <textarea id="adresse" wire:model="adresse" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Adresse complète de votre établissement"></textarea>
              @error('adresse') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div>
                <label for="nombre_chambres" class="block text-sm font-medium text-gray-700 mb-2">Nombre de chambres *</label>
                <input type="number" id="nombre_chambres" wire:model="nombre_chambres" min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('nombre_chambres') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
              </div>

              <div>
                <label for="capacite_max" class="block text-sm font-medium text-gray-700 mb-2">Capacité max *</label>
                <input type="number" id="capacite_max" wire:model="capacite_max" min="1"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('capacite_max') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
              </div>

              <div>
                <label for="prix_nuit" class="block text-sm font-medium text-gray-700 mb-2">Prix par nuit (FCFA) *</label>
                <input type="number" id="prix_nuit" wire:model="prix_nuit" min="0"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('prix_nuit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
              </div>
            </div>
          </div>
        </div>

        {{-- Section Services et équipements --}}
        <div>
          <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-concierge-bell mr-3 text-blue-600"></i>
            Services et équipements disponibles
          </h2>

          <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach($services_disponibles as $key => $service)
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="{{ $key }}"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">{{ $service }}</span>
            </label>
            @endforeach
          </div>
        </div>

        {{-- Section Description --}}
        <div>
          <h2 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
            <i class="fas fa-file-alt mr-3 text-blue-600"></i>
            Description et message
          </h2>

          <div class="space-y-6">
            <div>
              <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description de votre établissement *</label>
              <textarea id="description" wire:model="description" rows="5"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Décrivez votre établissement, ses atouts, l'ambiance, les activités à proximité... (minimum 50 caractères)"></textarea>
              @error('description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="message_supplementaire" class="block text-sm font-medium text-gray-700 mb-2">Message supplémentaire</label>
              <textarea id="message_supplementaire" wire:model="message_supplementaire" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                placeholder="Informations complémentaires que vous souhaitez partager avec notre équipe..."></textarea>
              @error('message_supplementaire') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
          </div>
        </div>

        {{-- Bouton d'envoi --}}
        <div class="text-center pt-6 border-t border-gray-200">
          <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-lg transition duration-200 inline-flex items-center">
            <i class="fas fa-paper-plane mr-2"></i>
            Envoyer ma demande
          </button>

          <p class="text-sm text-gray-500 mt-4">
            Notre équipe examinera votre demande et vous contactera dans les 48 heures.
          </p>
        </div>
      </form>
    </div>
  </div>
</div>