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
    <meta name="user-theme" content="{{ Auth::check() ? Auth::user()->theme : 'light' }}">
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

<body class="bg-white dark:bg-background-dark min-h-screen flex flex-col">
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
    <!-- Swiper JS -->
    <!--<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>-->

    @livewireScripts
    @livewire('partials.footer')
</body>

</html>