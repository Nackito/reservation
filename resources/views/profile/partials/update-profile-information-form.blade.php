<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')


        <div>
            <x-input-label for="firstname" :value="__('First name')" />
            <x-text-input id="firstname" name="firstname" type="text" class="mt-1 block w-full" :value="old('firstname', $user->firstname)" required autocomplete="given-name" />
            <x-input-error class="mt-2" :messages="$errors->get('firstname')" />
        </div>

        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Téléphone')" />
            <div class="flex gap-2">
                <select name="country_code" id="country_code" class="mt-1 block w-32 border-gray-300 rounded-md">
                    <option value="+225" @if(old('country_code', $user->country_code ?? '+225') == '+225') selected @endif>🇨🇮 +225</option>
                    <option value="+33" @if(old('country_code', $user->country_code ?? '') == '+33') selected @endif>🇫🇷 +33</option>
                    <option value="+226" @if(old('country_code', $user->country_code ?? '') == '+226') selected @endif>🇧🇫 +226</option>
                    <option value="+229" @if(old('country_code', $user->country_code ?? '') == '+229') selected @endif>🇧🇯 +229</option>
                    <option value="+223" @if(old('country_code', $user->country_code ?? '') == '+223') selected @endif>🇲🇱 +223</option>
                    <option value="+221" @if(old('country_code', $user->country_code ?? '') == '+221') selected @endif>🇸🇳 +221</option>
                    <option value="+1" @if(old('country_code', $user->country_code ?? '') == '+1') selected @endif>🇺🇸 +1</option>
                    <!-- Ajoutez d'autres pays si besoin -->
                </select>
                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            </div>
            <x-input-error class="mt-2" :messages="$errors->get('country_code')" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div>
                <p class="text-sm mt-2 text-gray-800">
                    {{ __('Your email address is unverified.') }}

                    <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                <p class="mt-2 font-medium text-sm text-green-600">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
                @endif
            </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
        </div>
    </form>
</section>