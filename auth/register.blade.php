@include('components.header')
@livewire('partials.navbar')
<div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto">
    <div class="flex h-full items-center">
        <main class="w-full max-w-md mx-auto p-6">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm dark:bg-gray-800 dark:border-gray-700">
                <div class="p-4 sm:p-7">
                    <div class="text-center">
                        <h1 class="block text-2xl font-bold text-gray-800 dark:text-white">S'inscrire sur Afridays</h1>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                            Rejoignez la communauté Afridays et découvrez les plus beaux hébergements de Côte d'Ivoire
                        </p>
                        <p class="mt-1 text-xs text-gray-500">
                            Vous avez déjà un compte ?
                            <a wire:navigate class="text-blue-600 decoration-2 hover:underline font-medium dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/login">
                                Connectez-vous ici
                            </a>
                        </p>
                    </div>
                    <hr class="my-5 border-slate-300">
                    <!-- Form -->
                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- Name -->
                        <div>
                            <x-input-label for="name" value="Nom complet" />
                            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Votre nom complet" />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <!-- Email Address -->
                        <div class="mt-4">
                            <x-input-label for="email" value="Adresse email" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="votre@email.com" />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Password -->
                        <div class="mt-4">
                            <x-input-label for="password" value="Mot de passe" />
                            <x-text-input id="password" class="block mt-1 w-full"
                                type="password"
                                name="password"
                                required autocomplete="new-password"
                                placeholder="Votre mot de passe" />
                            <x-input-error :messages="$errors->get('password')" class="mt-2" />
                        </div>

                        <!-- Confirm Password -->
                        <div class="mt-4">
                            <x-input-label for="password_confirmation" value="Confirmer le mot de passe" />
                            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password"
                                placeholder="Confirmez votre mot de passe" />
                            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-center mt-6">
                            <button type="submit" class="w-full py-3 px-4 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none">
                                <i class="fas fa-user-plus mr-2"></i>
                                Créer mon compte
                            </button>
                        </div>
                    </form>

                    <!-- Avantages de l'inscription -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <h3 class="text-sm font-semibold text-gray-700 mb-3 text-center">Pourquoi s'inscrire sur Afridays ?</h3>
                        <div class="space-y-2">
                            <div class="flex items-center text-xs text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Réservation rapide et sécurisée
                            </div>
                            <div class="flex items-center text-xs text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Accès aux meilleures offres exclusives
                            </div>
                            <div class="flex items-center text-xs text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Gestion de vos réservations en un clic
                            </div>
                            <div class="flex items-center text-xs text-gray-600">
                                <i class="fas fa-check-circle text-green-500 mr-2"></i>
                                Support client dédié 24h/7j
                            </div>
                        </div>
                    </div>
                    <!-- End Form -->
                </div>
            </div>
    </div>
</div>
@livewireScripts