<div class="min-h-screen bg-gray-50 py-12">
  {{-- Leaflet CSS --}}
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
    {{-- En-tête --}}
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-gray-900 mb-4">Proposer votre hébergement</h1>
      <p class="text-xl text-gray-600 max-w-3xl mx-auto">
        Vous possédez un établissement en Côte d'Ivoire ? Rejoignez Afridayz et faites découvrir votre hébergement à nos voyageurs.
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
            @php $user = Auth::user(); @endphp
            <div>
              <label for="prenom" class="block text-sm font-medium text-gray-700 mb-2">Prénom *</label>
              <input type="text" id="prenom" wire:model="prenom"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $user ? 'bg-gray-100 text-gray-500' : '' }}"
                placeholder="Votre prénom" @if($user) value="{{ $user->firstname ?? '' }}" readonly disabled @endif>
              @error('prenom') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="nom" class="block text-sm font-medium text-gray-700 mb-2">Nom *</label>
              <input type="text" id="nom" wire:model="nom"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $user ? 'bg-gray-100 text-gray-500' : '' }}"
                placeholder="Votre nom" @if($user) value="{{ $user->name ?? '' }}" readonly disabled @endif>
              @error('nom') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email *</label>
              <input type="email" id="email" wire:model="email"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $user ? 'bg-gray-100 text-gray-500' : '' }}"
                placeholder="votre@email.com" @if($user) value="{{ $user->email }}" readonly disabled @endif>
              @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>

            <div>
              <label for="telephone" class="block text-sm font-medium text-gray-700 mb-2">Téléphone *</label>
              <input type="tel" id="telephone" wire:model="telephone"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent {{ $user ? 'bg-gray-100 text-gray-500' : '' }}"
                placeholder="+225 XX XX XX XX XX" @if($user) value="{{ $user->telephone ?? $user->phone ?? '' }}" readonly disabled @endif>
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
                <label for="commune" class="block text-sm font-medium text-gray-700 mb-2">Commune</label>
                <input type="text" id="commune" wire:model="commune"
                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                  placeholder="Cocody, Yopougon, ... (optionnel)">
                @error('commune') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
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

            {{-- Section Localisation sur carte --}}
            <div>
              @php
              $mapTilerKey = config('services.maptiler.key');
              $mapTilerAttribution = config('services.maptiler.attribution');
              $mapTilerTilesUrl = config('services.maptiler.tiles_url');
              @endphp

              <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Localisation précise</label>
                <p class="text-xs text-gray-500 mb-3">Cliquez sur la carte pour indiquer l'emplacement exact de votre établissement</p>

                <div id="map" style="height: 400px; width: 100%;" class="rounded-lg border border-gray-300"></div>

                <input type="hidden" wire:model="latitude" id="latitude">
                <input type="hidden" wire:model="longitude" id="longitude">

                <div class="mt-2 text-xs text-gray-600">
                  <span id="coordinates-display">Coordonnées: Non sélectionnées</span>
                </div>
              </div>

              @push('scripts')
              <script>
                (function() {
                  function initContactMap() {
                    var el = document.getElementById('map');
                    if (!el || el.dataset.initialized === '1') return;
                    if (!window.L || typeof window.L.map !== 'function') return; // attend que Leaflet (via Vite) soit chargé

                    el.dataset.initialized = '1';

                    // Vue initiale centrée sur Abidjan, Côte d'Ivoire
                    var map = L.map(el).setView([5.3600, -4.0083], 12);

                    // Tuiles MapTiler (provenant de la config Laravel)
                    L.tileLayer(@json($mapTilerTilesUrl), {
                      attribution: @json($mapTilerAttribution),
                      maxZoom: 20,
                    }).addTo(map);

                    // Conserver une référence pour invalider la taille après re-render
                    el._leaflet_map = map;

                    var marker;
                    var accuracyCircle;
                    map.on('click', function(e) {
                      var lat = e.latlng.lat.toFixed(6);
                      var lng = e.latlng.lng.toFixed(6);

                      if (marker) {
                        marker.setLatLng(e.latlng);
                      } else {
                        marker = L.marker(e.latlng).addTo(map);
                      }

                      if (accuracyCircle) {
                        try {
                          map.removeLayer(accuracyCircle);
                        } catch (_) {}
                        accuracyCircle = null;
                      }

                      var latEl = document.getElementById('latitude');
                      var lngEl = document.getElementById('longitude');
                      var disp = document.getElementById('coordinates-display');
                      if (latEl) latEl.value = lat;
                      if (lngEl) lngEl.value = lng;
                      if (disp) disp.innerText = 'Coordonnées: ' + lat + ', ' + lng;
                    });

                    // === Géolocalisation (GPS) ===
                    function onLocationFound(e) {
                      var lat = e.latlng.lat.toFixed(6);
                      var lng = e.latlng.lng.toFixed(6);

                      if (marker) {
                        marker.setLatLng(e.latlng);
                      } else {
                        marker = L.marker(e.latlng).addTo(map);
                      }

                      if (accuracyCircle) {
                        try {
                          map.removeLayer(accuracyCircle);
                        } catch (_) {}
                      }
                      // Afficher un cercle d'accuracy si disponible
                      if (typeof e.accuracy === 'number') {
                        accuracyCircle = L.circle(e.latlng, {
                          radius: e.accuracy,
                          color: '#3b82f6',
                          fillColor: '#3b82f6',
                          fillOpacity: 0.1
                        });
                        accuracyCircle.addTo(map);
                      } else {
                        accuracyCircle = null;
                      }

                      var latEl = document.getElementById('latitude');
                      var lngEl = document.getElementById('longitude');
                      var disp = document.getElementById('coordinates-display');
                      if (latEl) latEl.value = lat;
                      if (lngEl) lngEl.value = lng;
                      if (disp) disp.innerText = 'Coordonnées (GPS): ' + lat + ', ' + lng;

                      try {
                        map.setView(e.latlng, Math.max(map.getZoom(), 15));
                      } catch (_) {}
                    }

                    function onLocationError(err) {
                      var disp = document.getElementById('coordinates-display');
                      if (disp) disp.innerText = 'Localisation impossible: ' + (err && err.message ? err.message : 'permission refusée ou contexte non sécurisé');
                    }

                    function tryGeolocate() {
                      if (!navigator.geolocation) {
                        onLocationError({
                          message: 'Géolocalisation non supportée par le navigateur'
                        });
                        return;
                      }
                      // Utiliser l’API interne de Leaflet (basée sur navigator.geolocation)
                      try {
                        map.locate({
                          setView: false,
                          enableHighAccuracy: true,
                          maximumAge: 10000,
                          timeout: 8000
                        });
                      } catch (_) {
                        // Fallback direct
                        navigator.geolocation.getCurrentPosition(function(pos) {
                          onLocationFound({
                            latlng: L.latLng(pos.coords.latitude, pos.coords.longitude),
                            accuracy: pos.coords.accuracy
                          });
                        }, onLocationError, {
                          enableHighAccuracy: true,
                          maximumAge: 10000,
                          timeout: 8000
                        });
                      }
                    }

                    map.on('locationfound', onLocationFound);
                    map.on('locationerror', onLocationError);

                    // Bouton de contrôle Leaflet pour se localiser
                    var LocateControl = L.Control.extend({
                      options: {
                        position: 'topleft'
                      },
                      onAdd: function() {
                        var btn = L.DomUtil.create('button', 'leaflet-bar leaflet-control');
                        btn.type = 'button';
                        btn.title = 'Me localiser';
                        btn.innerHTML = '📍';
                        btn.style.width = '34px';
                        btn.style.height = '34px';
                        btn.style.lineHeight = '30px';
                        btn.style.fontSize = '18px';
                        btn.style.cursor = 'pointer';
                        L.DomEvent.on(btn, 'click', function(ev) {
                          L.DomEvent.stopPropagation(ev);
                          L.DomEvent.preventDefault(ev);
                          tryGeolocate();
                        });
                        return btn;
                      }
                    });
                    map.addControl(new LocateControl());

                    // Sécuriser le rendu (si container caché au montage)
                    setTimeout(function() {
                      try {
                        map.invalidateSize();
                      } catch (_) {}
                    }, 150);
                  }

                  function tryInitNow() {
                    if (document.readyState === 'complete' || document.readyState === 'interactive') {
                      initContactMap();
                    } else {
                      document.addEventListener('DOMContentLoaded', initContactMap, {
                        once: true
                      });
                    }
                  }

                  tryInitNow();
                  document.addEventListener('livewire:load', initContactMap);
                  document.addEventListener('livewire:navigated', initContactMap);
                  if (window.livewire && typeof window.livewire.hook === 'function') {
                    window.livewire.hook('message.processed', function() {
                      initContactMap();
                      var el = document.getElementById('map');
                      if (el && el._leaflet_map) {
                        setTimeout(function() {
                          try {
                            el._leaflet_map.invalidateSize();
                          } catch (_) {}
                        }, 120);
                      }
                    });
                  }
                })();
              </script>
              @endpush
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

            <div class="mt-6">
              <label for="photos" class="block text-sm font-medium text-gray-700 mb-2">Photos de la propriété <span class="text-red-500">(minimum 5)</span></label>
              <input type="file" id="photos" wire:model="photos" multiple accept="image/*"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
              <p class="text-xs text-gray-500 mt-1">Ajoutez au moins 5 photos (formats acceptés : jpg, jpeg, png, webp).</p>
              @error('photos') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
              @if(isset($photos) && is_array($photos) && count($photos) > 0)
              <ul class="mt-2 text-xs text-gray-600">
                @foreach($photos as $photo)
                <li>{{ is_string($photo) ? $photo : (isset($photo->getClientOriginalName) ? $photo->getClientOriginalName() : 'Fichier sélectionné') }}</li>
                @endforeach
              </ul>
              @endif
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
            <!-- Ajout de services supplémentaires en dur (si non déjà dans $services_disponibles) -->
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Sécurité 24h/24"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Sécurité 24h/24</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Petit-déjeuner inclus"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Petit-déjeuner inclus</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Service de ménage"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Service de ménage</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Navette aéroport"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Navette aéroport</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Salle de réunion"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Salle de réunion</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Espace coworking"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Espace coworking</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Vue sur mer"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Vue sur mer</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Accès PMR"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Accès PMR (personnes à mobilité réduite)</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Aire de jeux enfants"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Aire de jeux enfants</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Restaurant sur place"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Restaurant sur place</span>
            </label>
            <label class="flex items-center space-x-3 cursor-pointer">
              <input type="checkbox" wire:model="services" value="Bar/lounge"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
              <span class="text-sm text-gray-700">Bar/lounge</span>
            </label>
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