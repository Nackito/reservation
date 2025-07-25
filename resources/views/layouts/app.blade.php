<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Afridays'}}</title>

    <!-- Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="resources/css/style.css">

    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <!-- CSS personnalisé pour l'autocomplétion -->
    <link rel="stylesheet" href="{{ asset('css/autocomplete.css') }}">

    <!-- CSS personnalisé pour la section héro -->
    <link rel="stylesheet" href="{{ asset('css/hero-section.css') }}">

    <!-- CSS personnalisé pour le carrousel de propriétés -->
    <link rel="stylesheet" href="{{ asset('css/property-carousel.css') }}">

    <!-- CSS personnalisé pour le carrousel des villes -->
    <link rel="stylesheet" href="{{ asset('css/cities-carousel.css') }}">

    <!-- CSS personnalisé pour les filtres de recherche -->
    <link rel="stylesheet" href="{{ asset('css/filters.css') }}">

    <!-- CSS personnalisé pour les propriétés populaires -->
    <link rel="stylesheet" href="{{ asset('css/popular-properties.css') }}">

    <!-- CSS global pour l'application -->
    <link rel="stylesheet" href="{{ asset('css/global-styles.css') }}">

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
    <script src="https://cdn.jsdelivr.net/npm/preline@1.0.0/dist/preline.min.js"></script>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Carrousel des propriétés
            const propertySwiper = new Swiper('.property-carousel', {
                //loop: true, // Permet de boucler les slides
                slidesPerView: 1, // Nombre de slides visibles
                spaceBetween: 10, // Espace entre les slides
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
            });

            // Carrousel des villes
            const citiesSwiper = new Swiper('.cities-carousel', {
                //loop: true, // Permet de boucler les slides
                slidesPerView: 1, // Nombre de slides visibles
                spaceBetween: 15, // Espace entre les slides
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
            });
        });
    </script>
    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!-- Swiper JS -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
    @livewireScripts
    @livewire('partials.footer')
</body>

</html>