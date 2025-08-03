<header class="flex z-[9999] sticky top-0 flex-wrap md:justify-start md:flex-nowrap w-full bg-white text-sm py-3 md:py-0 dark:bg-gray-800 shadow-md">
    <nav class="max-w-[85rem] w-full mx-auto px-4 md:px-6 lg:px-8" aria-label="Global">
        <div class="relative md:flex md:items-center md:justify-between">
            <div class="flex items-center justify-between">
                <a class="flex-none text-xl font-semibold dark:text-white dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600" href="/" aria-label="Brand">Afridays</a>
                <div class="md:hidden">
                    @guest
                    <a href="/login" class="flex items-center gap-2 py-2 px-4 rounded-lg text-sm font-semibold border border-blue-600 text-blue-600 hover:bg-blue-50 dark:border-blue-500 dark:text-blue-500 dark:hover:bg-blue-900 focus:outline-none focus:ring-1 focus:ring-blue-600">
                        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                            <circle cx="9" cy="7" r="4" />
                        </svg>
                        Connexion / Inscription
                    </a>
                    @else
                    <div class="flex items-center md:hidden">
                        <a href="{{ route('messaging') }}" class="flex items-center py-2 px-2 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-600">
                            <i class="fas fa-envelope text-blue-600 text-lg"></i>
                        </a>
                        <a href="{{ route('user.menu') }}" class="flex items-center py-2 px-4 rounded-lg text-sm font-semibold border border-gray-300 text-gray-800 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700 focus:outline-none focus:ring-1 focus:ring-gray-600">
                            <span>{{ Auth::user()->name }}</span>
                        </a>
                    </div>
                    @endguest
                </div>
            </div>

            <div id="navbar-collapse-with-animation" class="hs-collapse hidden overflow-hidden transition-all duration-300 basis-full grow md:block">
                <div class="max-h-[75vh] [&::-webkit-scrollbar]:w-2 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-track]:bg-gray-100 [&::-webkit-scrollbar-thumb]:bg-gray-300 dark:[&::-webkit-scrollbar-track]:bg-slate-700 dark:[&::-webkit-scrollbar-thumb]:bg-slate-500">
                    <div class="flex flex-col gap-x-0 mt-5 divide-y divide-dashed divide-gray-200 md:flex-row md:items-center md:justify-end md:gap-x-7 md:mt-0 md:ps-7 md:divide-y-0 md:divide-solid dark:divide-gray-700">

                        <a class="font-medium {{ request()->is('/') ? 'text-blue-600' : 'text-gray-500' }}  py-3 md:py-6 dark:text-blue-500 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600 md:flex hidden" href="/" aria-current="page">
                            <i class="fas fa-home mr-2"></i>Accueil
                        </a>

                        <a wire:navigate class="font-medium {{ request()->is('contact-hebergement') ? 'text-blue-600' : 'text-gray-500' }} py-3 md:py-6 dark:text-blue-500 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600 md:flex hidden" href="{{ route('contact.hebergement') }}">
                            <i class="fas fa-plus-circle mr-2"></i>Proposer un hébergement
                        </a>

                        @guest
                        <div class="pt-3 md:pt-0 flex flex-col md:flex-row gap-2">
                            <a wire:navigate class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-blue-600 text-blue-600 hover:bg-blue-50 disabled:opacity-50 disabled:pointer-events-none dark:border-blue-500 dark:text-blue-500 dark:hover:bg-blue-900 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600 md:flex hidden" href="/register">
                                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="m13 14 2 2 4-4" />
                                </svg>
                                S'inscrire
                            </a>
                            <a wire:navigate class="py-2.5 px-4 inline-flex items-center gap-x-2 text-sm font-semibold rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600 md:flex hidden" href="/login">
                                <svg class="flex-shrink-0 w-4 h-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                Se connecter
                            </a>
                        </div>
                        @endguest
                        @auth
                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 focus:ring-2 focus:ring-blue-500 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-gray-600 md:flex hidden" href="{{ route('user-reservations') }}">
                            Mes réservations
                        </a>

                        <a class="flex items-center gap-x-3.5 py-2 px-3 rounded-lg text-sm text-pink-600 hover:bg-pink-100 focus:ring-2 focus:ring-pink-500 dark:text-pink-400 dark:hover:bg-gray-700 dark:hover:text-pink-300 dark:focus:outline-none dark:focus:ring-1 dark:focus:ring-pink-600 md:flex hidden" href="{{ route('wishlist.index') }}">
                            <i class="fas fa-heart mr-2"></i>Mes souhaits
                        </a>

                        <div class="relative inline-block text-left z-[9999]">
                            <button type="button"
                                class="flex items-center gap-2 py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 focus:outline-none md:inline-flex hidden"
                                id="user-menu-button"
                                aria-expanded="false"
                                aria-haspopup="true"
                                onclick="document.getElementById('user-menu-dropdown').classList.toggle('hidden')">
                                <span>{{ Auth::user()->name }}</span>
                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                            <a href="{{ route('user.menu') }}" class="flex items-center py-2 px-3 rounded-lg text-sm text-gray-800 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-gray-300 focus:outline-none md:hidden">
                                <span>{{ Auth::user()->name }}</span>
                            </a>
                            <div id="user-menu-dropdown" class="user-menu-dropdown-fixed hidden md:block">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Profil</a>
                                <a href="{{ route('messaging') }}" class="block px-4 py-2 text-sm bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700">Messagerie</a>
                                <a href="{{ route('logout') }}" class="block px-4 py-2 text-sm bg-white text-gray-700 hover:bg-gray-100 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Se déconnecter</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </div>

                        @endauth
                    </div>
                </div>
            </div>
        </div>
    </nav>
</header>