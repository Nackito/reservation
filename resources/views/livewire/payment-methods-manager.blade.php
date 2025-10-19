<div class="space-y-6">
  <div>
    <h2 class="text-2xl font-extrabold text-gray-900 dark:text-gray-100">Moyens de paiement</h2>
    <p class="text-gray-600 dark:text-gray-300">Ajoutez ou supprimez vos moyens de paiement pour faciliter vos transactions.</p>
  </div>

  @if (session('status'))
  <div class="p-3 rounded border text-sm bg-blue-50 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-200 dark:border-blue-800">{{ session('status') }}</div>
  @endif

  <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
      @forelse ($methods as $m)
      <div class="p-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
          <div class="w-12 h-8 flex items-center justify-center rounded bg-white dark:bg-gray-900 ring-1 ring-gray-200 dark:ring-gray-700 overflow-hidden">
            @if ($m->brand === 'visa')
            <img src="{{ asset('images/brand-visa.svg') }}" alt="Visa" class="w-10 h-6 object-contain" loading="lazy">
            @elseif ($m->brand === 'mastercard')
            <img src="{{ asset('images/brand-mastercard.svg') }}" alt="Mastercard" class="w-10 h-6 object-contain" loading="lazy">
            @else
            <div class="w-10 h-6 bg-gray-200 dark:bg-gray-700 rounded"></div>
            @endif
          </div>
          <div class="text-sm text-gray-800 dark:text-gray-100">
            •••• {{ $m->last4 }}
            @if($m->is_default)
            <span class="ml-2 inline-flex items-center rounded-full bg-green-100 text-green-800 text-xs px-2 py-0.5">Par défaut</span>
            @endif
          </div>
        </div>
        <div class="flex items-center gap-3">
          <div class="text-sm text-gray-600 dark:text-gray-300">{{ str_pad($m->exp_month, 2, '0', STR_PAD_LEFT) }}-{{ $m->exp_year }}</div>
          @unless($m->is_default)
          <button wire:click="setDefault({{ $m->id }})" type="button" class="text-sm text-gray-700 hover:text-gray-900">Définir par défaut</button>
          @endunless
          <button wire:click="remove({{ $m->id }})" type="button" class="text-blue-600 hover:text-blue-700 text-sm">Supprimer</button>
        </div>
      </div>
      @empty
      <div class="p-6 text-sm text-gray-600 dark:text-gray-300">Aucune carte enregistrée.</div>
      @endforelse
    </div>
  </div>

  <div class="text-center text-gray-600 dark:text-gray-300">Payez avec une nouvelle carte</div>

  <form wire:submit.prevent="add" x-data="paymentForm()" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-6 space-y-4">
    <!-- Logos -->
    <div class="flex flex-wrap items-center gap-2 opacity-80">
      <img src="{{ asset('images/brand-mastercard.svg') }}" alt="Mastercard" class="w-10 h-6 object-contain" loading="lazy">
      <img src="{{ asset('images/brand-visa.svg') }}" alt="Visa" class="w-10 h-6 object-contain" loading="lazy">
    </div>

    <div>
      <label for="pm-holder" class="block text-sm text-gray-700 dark:text-gray-200 mb-1">Nom du titulaire de la carte <span class="text-red-500">*</span></label>
      <input id="pm-holder" wire:model="cardholder" type="text" autocomplete="cc-name" class="w-full rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="">
      @error('cardholder') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label for="pm-number" class="block text-sm text-gray-700 dark:text-gray-200 mb-1">Numéro de carte <span class="text-red-500">*</span></label>
      <div class="relative">
        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">💳</span>
        <input id="pm-number" x-model="number" x-on:input="formatNumber" wire:model.defer="number" type="text" inputmode="numeric" autocomplete="cc-number" class="w-full pl-9 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="">
      </div>
      @error('number') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div>
      <label for="pm-exp" class="block text-sm text-gray-700 dark:text-gray-200 mb-1">Date d'expiration <span class="text-red-500">*</span></label>
      <input id="pm-exp" x-model="exp" x-on:input="formatExp" wire:model.defer="exp" type="text" inputmode="numeric" autocomplete="cc-exp" class="w-40 rounded border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" placeholder="MM / AA">
      @error('exp') <div class="text-xs text-red-600 mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="pt-2">
      <button type="submit" :class="{'opacity-50 pointer-events-none': !valid}" class="inline-flex items-center justify-center px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">Enregistrer</button>
    </div>
  </form>

  <script>
    function paymentForm() {
      return {
        number: '',
        exp: '',
        valid: false,
        formatNumber() {
          // remove non digits
          let digits = this.number.replace(/\D/g, '');
          // group by 4
          this.number = digits.replace(/(.{4})/g, '$1 ').trim();
          this.checkValid();
        },
        formatExp() {
          let digits = this.exp.replace(/\D/g, '');
          if (digits.length > 2) digits = digits.slice(0, 2) + ' / ' + digits.slice(2, 4);
          this.exp = digits;
          this.checkValid();
        },
        luhn(digits) {
          let sum = 0;
          let alt = false;
          for (let i = digits.length - 1; i >= 0; i--) {
            let d = parseInt(digits.charAt(i), 10);
            if (alt) {
              d *= 2;
              if (d > 9) d -= 9;
            }
            sum += d;
            alt = !alt;
          }
          return sum % 10 === 0;
        },
        checkValid() {
          const digits = this.number.replace(/\D/g, '');
          const expDigits = this.exp.replace(/\D/g, '');
          const okNum = digits.length >= 12 && digits.length <= 19 && this.luhn(digits);
          const okExp = expDigits.length === 4; // MMYY
          this.valid = okNum && okExp;
        }
      }
    }
  </script>
</div>
</div>