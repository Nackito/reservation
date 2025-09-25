@include('components.header')
@livewire('partials.navbar')

<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="flex h-full items-center">
        <main class="w-full max-w-md mx-auto p-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 sm:p-7">
                    <div class="text-center">
                        <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">Se connecter à Afridayz</h1>
                        <div class="mb-4 flex flex-col items-center">
                            <a href="{{ url('/auth/redirect/google') }}" class="w-full flex items-center justify-center gap-2 py-2 px-4 rounded-lg border border-gray-300 bg-white text-gray-700 font-semibold shadow hover:bg-gray-50 transition mb-2">
                                <img src="https://www.svgrepo.com/show/475656/google-color.svg" alt="Google" class="w-5 h-5">
                                Se connecter avec Google
                            </a>
                            <span class="text-gray-400 text-xs">ou</span>
                        </div>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Vous n'avez pas encore de compte ?
                            <a wire:navigate class="text-blue-600 decoration-2 hover:underline font-medium dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/register">
                                Inscrivez-vous ici
                            </a>
                        </p>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            <span>Si vous êtes employé, <a href="/admin/login" class="text-blue-600 decoration-2 hover:underline font-medium">cliquez ici</a> pour accéder à l'espace employé.</span>
                        </p>
                    </div>

                    <hr class="my-5 border-slate-300">

                    <!-- Form -->
                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- Email Address -->
                        <div>
                            <x-input-label for="email" :value="__('Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="password" :value="__('Password')" />

                            <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="current-password" />

                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>


                        <!-- 2FA Code (si activé) -->
                        @php
                        $user = \App\Models\User::where('email', old('email'))->first();
                        @endphp
                        @if($user && $user->two_factor_secret)
                        <div class="mt-4">
                            <x-input-label for="two_factor_code" :value="'Code de vérification (2FA)'" />
                            <x-text-input id="two_factor_code" class="block mt-1 w-full" type="text" name="two_factor_code" autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*" />
                            <x-input-error :messages="$errors->get('two_factor_code')" class="mt-2" />
                        </div>
                        @endif

                        <!-- Remember Me -->
                        <div class="block mt-4">
                            <label for="remember_me" class="inline-flex items-center">
                                <input id="remember_me" type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" name="remember">
                                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
                            </label>
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            @if (Route::has('password.request'))
                            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('password.request') }}">
                                {{ __('Forgot your password?') }}
                            </a>
                            @endif

                            <button type="submit" class="ms-3 bg-indigo-600 text-white px-4 py-2 rounded-md shadow hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                {{ __('Se connecter') }}
                            </button>
                        </div>
                    </form>
                    <!-- End Form -->
                </div>
            </div>
    </div>
</div>

@include('livewire.partials.footer')
@livewireScripts