<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\Booking;
use Filament\Forms\Components\Livewire;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\Facades\LivewireAlert;

class PropertyManager extends Component
{
    use WithFileUploads;
    public $properties;
    public $bookings;
    public $receivedBookings;
    public $pendingBookings;
    public $acceptedBookings;
    public $name;
    public $description;
    public $price_per_night;
    public $propertyId;
    public $images = [];

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'price_per_night' => 'required|numeric',
        'images.*' => 'nullable|image|max:1024',
    ];

    public function mount()
    {
        $user = Auth::user();
        $this->properties = Property::with('images')->where('user_id', $user->id)->get();
        $this->bookings = Booking::with('property')->where('user_id', $user->id)->get();

        //$this->bookings = Booking::whereIn('property_id', $this->properties->pluck('id'))->get();
        $this->receivedBookings = Booking::with('user')
            ->whereIn('property_id', $this->properties->pluck('id'))
            ->where('user_id', '!=', $user->id)
            ->get();
    }

    public function deleteBooking($id)
    {
        Booking::find($id)->delete();
        $this->bookings = Booking::where('user_id', Auth::id())->get();
        $this->receivedBookings = Booking::with('user')
            ->whereIn('property_id', $this->properties->pluck('id'))
            ->where('user_id', '!=', Auth::id())
            ->get();
        LivewireAlert::title('Réservation annulée avec succès!')->success()->show();
    }

    public function acceptBooking($id)
    {
        $booking = Booking::find($id);
        // Ajoutez ici la logique pour accepter la réservation
        $booking->status = 'accepted';
        $booking->save();
        $this->receivedBookings = Booking::with('user')
            ->whereIn('property_id', $this->properties->pluck('id'))
            ->where('user_id', '!=', Auth::id())
            ->get();
        LivewireAlert::title('Réservation acceptée avec succès!')->success()->show();
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->price_per_night = '';
        $this->propertyId = null;
        $this->images = [];
    }

    public function store()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        $this->validate();

        $property = Property::create([
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => $this->price_per_night,
            'image' => $this->images ? $this->images[0]->store('images', 'public') : null,
            'user_id' => Auth::id(),
        ]);

        foreach ($this->images as $index => $image) {
            if ($index > 0) {
                $imagePath = $image->store('images', 'public');
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        $this->resetInputFields();
        $this->properties = Property::with('images')->where('user_id', Auth::id())->get();
        $this->bookings = Booking::with('property')->where('user_id', $user->id)->get();
        //$this->bookings = Booking::whereIn('property_id', $this->properties->pluck('id'))->get();
        $this->receivedBookings = Booking::with('user')
            ->whereIn('property_id', $this->properties->pluck('id'))
            ->where('user_id', '!=', Auth::id())
            ->get();
    }

    public function edit($id)
    {
        $property = Property::findOrFail($id);
        $this->propertyId = $property->id;
        $this->name = $property->name;
        $this->description = $property->description;
        $this->price_per_night = $property->price_per_night;
        $this->images = [];
    }

    public function update()
    {
        $this->validate();
        $property = Property::find($this->propertyId);

        // Ne mettre à jour l'image que si une nouvelle image est téléchargée
        if ($this->images) {
            $imagePath = $this->images[0]->store('images', 'public');
        } else {
            $imagePath = $property->image;
        }
        $property->update([
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => $this->price_per_night,
            'image' => $imagePath,
        ]);

        foreach ($this->images as $index => $image) {
            if ($index > 0) {
                $imagePath = $image->store('images', 'public');
                PropertyImage::create([
                    'property_id' => $property->id,
                    'image_path' => $imagePath,
                ]);
            }
        }

        $this->resetInputFields();
        $this->properties = Property::with('images')->where('user_id', Auth::id())->get();
        $this->bookings = Booking::with('property')->where('user_id', $user->id)->get();
        //$this->bookings = Booking::whereIn('property_id', $this->properties->pluck('id'))->get();
        $this->receivedBookings = Booking::with('user')
            ->whereIn('property_id', $this->properties->pluck('id'))
            ->where('user_id', '!=', Auth::id())
            ->get();
    }

    public function delete($id)
    {
        Property::find($id)->delete();
        $this->properties = Property::with('images')->where('user_id', Auth::id())->get();
        $this->bookings = Booking::with('property')->where('user_id', $user->id)->get();
        //$this->bookings = Booking::whereIn('property_id', $this->properties->pluck('id'))->get();
        $this->receivedBookings = Booking::with('user')
            ->whereIn('property_id', $this->properties->pluck('id'))
            ->where('user_id', '!=', Auth::id())
            ->get();
    }

    public function render()
    {
        return view('livewire.property-manager')->extends('layouts.app')->section('content');
    }
}
