<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Property;
use App\Models\PropertyImage;
use Livewire\WithFileUploads;

class PropertyManager extends Component
{
    use WithFileUploads;
    public $properties;
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
        $this->properties = Property::with('images')->get();
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
        $this->validate();

        $property = Property::create([
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => $this->price_per_night,
            'image' => $this->images ? $this->images[0]->store('images', 'public') : null,
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
        $this->properties = Property::with('images')->get();
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
        $this->properties = Property::with('images')->get();
    }

    public function delete($id)
    {
        Property::find($id)->delete();
        $this->properties = Property::with('images')->get();
    }

    public function render()
    {
        return view('livewire.property-manager');
    }
}
