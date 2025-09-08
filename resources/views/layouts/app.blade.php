<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @if(Auth::check() && Auth::user()->theme == 'dark') class="dark" @endif>

<head>
    <style>
        html,
        body {
            overflow-x: hidden !important;
        }
    </style>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- WebView & mobile friendly -->
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <title>{{ $title ?? 'Afridayz'}}</title>


    <!-- Flatpickr CSS (optionnel, à activer si besoin) -->
    <!--<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">-->

    <!-- leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
        integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
        crossorigin="" />
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
        integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
        crossorigin=""></script>
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    @vite('resources/css/style.css')
    @livewireStyles
</head>

<body class="bg-white min-h-screen flex flex-col">
    @livewire('partials.navbar')

    <!-- Page Heading -->


    <!-- Page Content -->
    <main class="flex-grow">
        {{ $slot }}

    </main>


    <!-- Inclure les scripts Preline -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/preline@1.0.0/dist/preline.min.js"></script> -->
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
        let propertySwiper = null;
        let citiesSwiper = null;

        function destroyCarousels() {
            // console.log('Destroying carousels...');
            if (propertySwiper) {
                propertySwiper.destroy(true, true);
                propertySwiper = null;
            }
            if (citiesSwiper) {
                citiesSwiper.destroy(true, true);
                citiesSwiper = null;
            }
        }

        function initializeCarousels() {
            // console.log('Initializing carousels...');

            // Détruire d'abord les instances existantes
            destroyCarousels();

            // Attendre que le DOM soit vraiment prêt
            setTimeout(function() {
                // Réinitialiser le carrousel des propriétés
                const propertyCarousel = document.querySelector('.property-carousel');
                if (propertyCarousel) {
                    // console.log('Creating property carousel...');
                    propertySwiper = new Swiper('.property-carousel', {
                        slidesPerView: 1,
                        spaceBetween: 10,
                        observer: true,
                        observeParents: true,
                        watchOverflow: true,
                        navigation: {
                            nextEl: '.property-carousel .swiper-button-next',
                            prevEl: '.property-carousel .swiper-button-prev',
                        },
                        pagination: {
                            el: '.property-carousel .swiper-pagination',
                            clickable: true,
                        },
                        breakpoints: {
                            640: {
                                slidesPerView: 2,
                                spaceBetween: 20,
                            },
                            768: {
                                slidesPerView: 3,
                                spaceBetween: 30,
                            },
                            1024: {
                                slidesPerView: 4,
                                spaceBetween: 40,
                            },
                        },
                        on: {
                            init: function() {
                                // console.log('Property carousel initialized');
                            }
                        }
                    });
                }

                // Réinitialiser le carrousel des villes
                const citiesCarousel = document.querySelector('.cities-carousel');
                if (citiesCarousel) {
                    // console.log('Creating cities carousel...');
                    citiesSwiper = new Swiper('.cities-carousel', {
                        slidesPerView: 1,
                        spaceBetween: 15,
                        observer: true,
                        observeParents: true,
                        watchOverflow: true,
                        navigation: {
                            nextEl: '.cities-carousel .swiper-button-next',
                            prevEl: '.cities-carousel .swiper-button-prev',
                        },
                        pagination: {
                            el: '.cities-carousel .swiper-pagination',
                            clickable: true,
                        },
                        breakpoints: {
                            480: {
                                slidesPerView: 2,
                                spaceBetween: 15,
                            },
                            640: {
                                slidesPerView: 3,
                                spaceBetween: 20,
                            },
                            768: {
                                slidesPerView: 4,
                                spaceBetween: 25,
                            },
                            1024: {
                                slidesPerView: 5,
                                spaceBetween: 30,
                            },
                        },
                        on: {
                            init: function() {
                                // console.log('Cities carousel initialized');
                            }
                        }
                    });
                }
            }, 300);
        }

        // Initialiser les carrousels au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            // console.log('DOM loaded, initializing carousels...');
            initializeCarousels();
        });

        // Gestion des événements Livewire
        document.addEventListener('livewire:init', () => {
            // console.log('Livewire initialized');
            Livewire.on('refresh-carousels', () => {
                // console.log('Received refresh-carousels event');
                setTimeout(() => {
                    initializeCarousels();
                }, 500);
            });
        });

        // Support pour Livewire v2 (fallback)
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.livewire !== 'undefined') {
                window.livewire.on('refresh-carousels', () => {
                    // console.log('Received refresh-carousels event (v2)');
                    setTimeout(() => {
                        initializeCarousels();
                    }, 500);
                });
            }
        });
    </script>
    <!-- Swiper JS -->
    <!--<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>-->

    @livewireScripts
    @livewire('partials.footer')
</body>

</html>