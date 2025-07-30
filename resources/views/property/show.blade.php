@extends('layouts.app')

@section('content')
<div class="container mx-auto py-8">
  <h1 class="text-3xl font-bold mb-6 text-gray-800">{{ $property->name }}</h1>
  <div class="mb-4">
    <span class="text-gray-600">{{ $property->city }}, {{ $property->municipality }}</span>
  </div>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div>
      @if($property->images->count())
      <img src="{{ asset('storage/' . $property->images->first()->image_path) }}" alt="Image principale" class="w-full h-64 object-cover rounded mb-4">
      <div class="flex gap-2">
        @foreach($property->images as $image)
        <img src="{{ asset('storage/' . $image->image_path) }}" alt="Image" class="w-20 h-20 object-cover rounded">
        @endforeach
      </div>
      @else
      <img src="https://via.placeholder.com/600x400" alt="Aucune image" class="w-full h-64 object-cover rounded mb-4">
      @endif
    </div>
    <div>
      <h2 class="text-xl font-semibold mb-2">Description</h2>
      <p class="text-gray-700 mb-4">{!! $property->description !!}</p>
      <div class="mb-2">
        <span class="font-bold">Prix par nuit :</span> {{ $property->price_per_night }} €
      </div>
      <!-- Ajoute ici d'autres infos si besoin -->
    </div>
  </div>
</div>
@endsection