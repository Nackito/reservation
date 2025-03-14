<div class="carousel relative">
  <div class="carousel-inner relative overflow-hidden w-full">
    @foreach($properties as $property)
    <div class="carousel-item @if($loop->first) active @endif relative float-left w-full">
      <img src="{{ $property->image_url }}" class="block w-full" alt="{{ $property->name }}">
      <div class="carousel-caption absolute text-center">
        <h5 class="text-xl">{{ $property->name }}</h5>
        <p>{{ $property->address }}</p>
        <p>{{ $property->price_per_night }} € par nuit</p>
        <a href="{{ route('booking', ['property' => $property->id]) }}" class="bg-primary text-white px-4 py-2 rounded">RESERVATION</a>
      </div>
    </div>
    @endforeach
  </div>
  <button class="carousel-control-prev absolute top-0 bottom-0 left-0 z-10 flex items-center justify-center p-0 text-center border-0 hover:outline-none hover:no-underline focus:outline-none focus:no-underline" type="button" data-bs-target="#carouselExampleControls" data-bs-slide="prev">
    <span class="carousel-control-prev-icon inline-block bg-no-repeat" aria-hidden="true"></span>
    <span class="visually-hidden">Previous</span>
  </button>
  <button class="carousel-control-next absolute top-0 bottom-0 right-0 z-10 flex items-center justify-center p-0 text-center border-0 hover:outline-none hover:no-underline focus:outline-none focus:no-underline" type="button" data-bs-target="#carouselExampleControls" data-bs-slide="next">
    <span class="carousel-control-next-icon inline-block bg-no-repeat" aria-hidden="true"></span>
    <span class="visually-hidden">Next</span>
  </button>
</div>