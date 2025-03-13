@include('components.header')

<div class="bg-gray-100">
    <div class="container mx-auto py-8">
        <h1 class="text-2xl font-bold mb-4">Bienvenue sur notre site</h1>
        <p class="text-gray-600">Lorem ipsum dolor sit amet, consectetur adipisicing elit
            . Quos autem, eaque, voluptas, officia culpa ipsam laudantium
            quae quia nemo alias quidem doloribus.</p>
    </div>
</div>
<div class="container mx-auto mt-8">
    <h1 class="text-2xl font-bold mb-4">Quelques propriétés</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($properties as $property)
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            @if($property->image)
            <img src="{{ asset('storage/' . $property->image) }}" alt="{{ $property->name }}" class="w-full h-48 object-cover">
            @endif
            <div class="p-4">
                <h3 class="text-lg text-gray-800">{{ $property->name ?? 'Nom non disponible' }}</h3>
                <p class="text-gray-500">{{ $property->description ?? 'Description non disponible' }}</p>
                <p class="text-gray-600">{{ $property->price_per_night ?? 'Prix non disponible' }} € par nuit</p>
                <div class="mt-4 flex justify-between">
                    <a href="{{ route('booking-manager', ['propertyId' => $property->id]) }}" class="bg-blue-500 text-white py-1 px-2 rounded">Réserver</a>
                </div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@include('components.footer')
</body>

</html>