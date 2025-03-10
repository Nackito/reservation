@include('components.header')

<body class="bg-gray-100">
    </div>
    </nav>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto p-4">
        @yield('content')
    </main>

    <!-- Footer -->
    @livewireScripts
</body>

@include('components.footer')

</html>