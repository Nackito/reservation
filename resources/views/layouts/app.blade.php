<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <style>
        html,
        body {
            overflow-x: hidden !important;
        }

        /* Par défaut, footer collé en bas */
        .footer-mobile {
            bottom: 0;
        }

        /* Quand la page est affichée dans l'APK/WebView, on expose la variable et on décale le footer */
        html.webview {
            --apk-offset-bottom: 40px;
        }

        html.webview .footer-mobile {
            bottom: calc(env(safe-area-inset-bottom) + var(--apk-offset-bottom));
        }

        html.webview .mobile-scrim {
            display: block;
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: calc(env(safe-area-inset-bottom) + var(--apk-offset-bottom));
            background: #000;
            z-index: 40;
            pointer-events: none;
        }

        .mobile-scrim {
            display: none;
        }
    </style>

    <script>
        // Détection légère d'un WebView Android et ajout de la classe html.webview
        (function() {
            try {
                var ua = navigator.userAgent || '';
                var android = /Android/i.test(ua);
                var isWv = /\bwv\b/i.test(ua) || /Version\/\d+\.\d+/i.test(ua);
                var bridge = !!(window.ReactNativeWebView || window.AndroidInterface || window.flutter_inappwebview || window.cordova || window.Capacitor);
                var noChrome = android && !/Chrome\//i.test(ua);
                if (android && (isWv || bridge || noChrome)) {
                    document.documentElement.classList.add('webview');
                }
            } catch (e) {}
        })();
    </script>

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- WebView & mobile friendly -->
    <meta name="theme-color" content="#2563eb">
    <meta name="user-theme" content="{{ Auth::check() ? (Auth::user()->theme ?? 'system') : 'system' }}">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <meta name="apple-mobile-web-app-title" content="Afridayz">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" href="{{ asset('favicon.ico') }}">

    <title>{{ $title ?? 'Afridayz'}}</title>


    <!-- Resource Hints -->
    <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="dns-prefetch" href="//cdnjs.cloudflare.com">
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="dns-prefetch" href="//unpkg.com">
    <link rel="preconnect" href="https://api.maptiler.com" crossorigin>
    <link rel="dns-prefetch" href="//api.maptiler.com">
    <link rel="preconnect" href="https://api-eu.pusher.com" crossorigin>
    <link rel="dns-prefetch" href="//api-eu.pusher.com">

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
    @stack('scripts')
    @livewire('partials.footer')
</body>

</html>