<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Reservation'}}</title>


    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    @livewireStyles
</head>

<body class="bg-slate-200 dark:bg-slate-700">
    @livewire('partials.navbar')

    <!-- Page Heading -->


    <!-- Page Content -->
    <main>
        {{ $slot }}

    </main>


    @livewire('partials.footer')
    <!-- Inclure les scripts Preline -->
    <script src="https://cdn.jsdelivr.net/npm/preline@1.0.0/dist/preline.min.js"></script>
    <!-- Scripts -->
    @livewireScripts
</body>

</html>