@include('components.header')

<div class="min-h-screen flex flex-col">
    <div class="bg-gray-100">
        <div class="container mx-auto py-8">
            <h1 class="text-2xl font-bold mb-4">Bienvenue sur notre site</h1>
            <p class="text-gray-600">Lorem ipsum dolor sit amet, consectetur adipisicing elit
                . Quos autem, eaque, voluptas, officia culpa ipsam laudantium
                quae quia nemo alias quidem doloribus.</p>
        </div>
    </div>

    <div class="container mx-auto mt-12 px-4 flex-grow">
        <h1 class="text-3xl font-bold mb-8 text-center text-gray-800">Quelques propriétés</h1>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 max-w-6xl mx-auto pb-12">
            @foreach($properties as $property)
            <div class="bg-white shadow-lg rounded-lg overflow-hidden hover:shadow-xl transition-shadow duration-300">
                @if($property->image)
                <img src="{{ asset('storage/' . $property->image) }}" alt="{{ $property->name }}" class="w-full h-64 object-cover">
                @endif
                <div class="p-8">
                    <h3 class="text-2xl font-semibold text-gray-800 mb-4">{{ $property->name ?? 'Nom non disponible' }}</h3>
                    <p class="text-gray-500 mb-6 text-lg leading-relaxed">{{ $property->description ?? 'Description non disponible' }}</p>
                    <p class="text-xl font-bold text-blue-600 mb-6">{{ $property->price_per_night ?? 'Prix non disponible' }} FrCFA par nuit</p>
                    <div class="mt-6">
                        <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}"
                            class="bg-blue-600 hover:bg-blue-700 text-white py-4 px-8 rounded-lg font-medium transition-colors duration-200 inline-flex items-center justify-center w-full text-lg">
                            <i class="fas fa-calendar-check mr-3"></i>
                            Réserver maintenant
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    @include('components.footer')
</div>
</body>

</html>