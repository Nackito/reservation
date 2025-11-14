<div>

    <!-- Footer mobile de navigation rapide, visible uniquement sur mobile -->
    <nav class="footer-mobile w-full flex-shrink-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 shadow-sm flex justify-between items-center px-6 py-2 md:hidden fixed inset-x-0 z-50">
        <a href="{{ route('home') }}" class="flex flex-col items-center text-gray-500 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
            <i class="fas fa-search text-xl"></i>
            <span class="text-xs">Recherche</span>
        </a>
        <a href="{{ route('wishlist.index') }}" class="flex flex-col items-center text-gray-500 dark:text-gray-300 hover:text-pink-600 dark:hover:text-pink-400 transition">
            <i class="fas fa-heart text-xl"></i>
            <span class="text-xs">Favoris</span>
        </a>
        <a href="{{ route('user-reservations') }}" class="flex flex-col items-center text-gray-500 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
            <i class="fas fa-calendar-check text-xl"></i>
            <span class="text-xs">Réservations</span>
        </a>

        <a href="{{ route('user.menu') }}" class="flex flex-col items-center text-gray-500 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
            <i class="fas fa-user text-xl"></i>
            <span class="text-xs">Profil</span>
        </a>
    </nav>

    <!-- Scrim noir entre le footer et la barre système (pousse le footer au-dessus des boutons) -->
    <div class="mobile-scrim md:hidden" aria-hidden="true"></div>
    <!-- Spacer pour éviter que le contenu soit masqué par la nav fixe sur mobile -->
    <div class="md:hidden" style="height: calc(56px + env(safe-area-inset-bottom));"></div>
    <footer class="bg-gray-900 dark:bg-gray-900 w-full hidden md:block">
        <div class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 lg:pt-20 mx-auto">
            <!-- Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-6">
                <div class="col-span-full lg:col-span-1">
                    <a class="flex-none text-xl font-semibold text-white dark:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/" aria-label="Brand">Afridayz</a>
                </div>
                <!-- End Col -->

                <div class="col-span-1">
                    <h4 class="font-semibold text-gray-100 dark:text-gray-100">Préférences</h4>

                    <div class="mt-3 grid space-y-3">
                        <p><a class="inline-flex gap-x-2 text-gray-400 dark:text-gray-400 hover:text-gray-200 dark:hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('preferences.currency') }}">Devises</a></p>
                        <p><a class="inline-flex gap-x-2 text-gray-400 dark:text-gray-400 hover:text-gray-200 dark:hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('preferences.display') }}">Affichage</a></p>
                    </div>
                </div>
                <!-- End Col -->

                <div class="col-span-1">
                    <h4 class="font-semibold text-gray-100 dark:text-gray-100">A propos</h4>

                    <div class="mt-3 grid space-y-3">
                        <p><a class="inline-flex gap-x-2 text-gray-400 dark:text-gray-400 hover:text-gray-200 dark:hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="#">A propos de nous</a></p>
                        <p><a class="inline-flex gap-x-2 text-gray-400 dark:text-gray-400 hover:text-gray-200 dark:hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="#">Blog</a></p>
                        <p><a class="inline-flex gap-x-2 text-gray-400 dark:text-gray-400 hover:text-gray-200 dark:hover:text-gray-200 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="{{ route('privacy') }}">Politique de confidentialité</a></p>
                    </div>
                </div>
                <!-- End Col -->

                <div class="col-span-2">
                    <h4 class="font-semibold text-gray-100 dark:text-gray-100">Recevez nos offres</h4>

                    <form>
                        <div class="mt-4 flex flex-col items-center gap-2 sm:flex-row sm:gap-3 bg-white dark:bg-gray-800 rounded-lg p-2">
                            <div class="w-full">
                                <input type="text" id="hero-input" name="hero-input" class="py-3 px-4 block w-full border-transparent rounded-lg text-sm focus:border-blue-500 focus:ring-blue-500 disabled:opacity-50 disabled:pointer-events-none dark:bg-slate-900 dark:border-transparent dark:text-gray-400 dark:focus:ring-gray-600" placeholder="Entrez votre email">
                            </div>
                            <a class="w-full sm:w-auto whitespace-nowrap p-3 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="#">Souscrire</a>
                        </div>

                    </form>
                </div>
                <!-- End Col -->
            </div>
            <!-- End Grid -->

            <div class="mt-5 sm:mt-12 grid gap-y-2 sm:gap-y-0 sm:flex sm:justify-between sm:items-center">
                <div class="flex justify-between items-center">
                    <p class="text-sm text-gray-400 dark:text-gray-400">© 2025 Nackito. Tous droits réservés.</p>
                </div>
                <!-- End Col -->

                <!-- Social Brands -->
                <div>
                    <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-white dark:text-white hover:bg-white/10 dark:hover:bg-white/20 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-gray-600 dark:focus:ring-gray-500" href="https://www.facebook.com/afridayzci/" aria-label="Facebook" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-facebook text-lg" aria-hidden="true"></i>
                    </a>
                    <!-- Spacer pour éviter que le contenu soit masqué par la nav fixe sur mobile -->
                    <a class="w-10 h-10 inline-flex justify-center items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent text-white dark:text-white hover:bg-white/10 dark:hover:bg-white/20 disabled:opacity-50 disabled:pointer-events-none focus:outline-none focus:ring-1 focus:ring-gray-600 dark:focus:ring-gray-500" href="https://www.instagram.com/afridayz225/" aria-label="Instagram" target="_blank" rel="noopener noreferrer">
                        <i class="fab fa-instagram text-lg" aria-hidden="true"></i>
                    </a>


                </div>
                <!-- End Social Brands -->
            </div>
        </div>
    </footer>
</div>