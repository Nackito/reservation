<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

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
    <meta name="user-theme" content="{{ Auth::check() ? (Auth::user()->theme ?? 'system') : 'system' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <title>{{ $title ?? 'Afridayz'}}</title>


    <!-- Leaflet chargé via Vite (ESM) dans resources/js/app.js -->
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <script>
        // Applique le thème le plus tôt possible (évite le flash visuel)
        (function() {
            try {
                var ls = localStorage.getItem('theme');
                var m = document.cookie.match(/(?:^|;\s*)theme=([^;]+)/);
                var cookieTheme = m ? decodeURIComponent(m[1]) : null;
                var serverTheme = document.querySelector('meta[name="user-theme"]')?.getAttribute('content') || 'system';
                var t = ls || cookieTheme || serverTheme || 'system';
                if (t === 'system') {
                    t = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                if (t === 'dark') document.documentElement.classList.add('dark');
                else document.documentElement.classList.remove('dark');
            } catch (e) {}
        })();
    </script>
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

    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    @livewireScripts
    @livewire('partials.footer')
</body>

</html>