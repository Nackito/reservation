<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Property;
use Livewire\WithFileUploads;

class PropertyManager extends Component
{
    use WithFileUploads;
    public $properties;
    public $name;
    public $description;
    public $price_per_night;
    public $propertyId;
    public $image;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'price_per_night' => 'required|numeric',
        'image' => 'nullable|image|max:1024',
    ];

    public function mount()
    {
        $this->properties = Property::all();
    }

    public function resetInputFields()
    {
        $this->name = '';
        $this->description = '';
        $this->price_per_night = '';
        $this->propertyId = null;
        $this->image = null;
    }

    public function store()
    {
        $this->validate();
        $imagePath = $this->image ? $this->image->store('images', 'public') : null;


        Property::create([
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => $this->price_per_night,
            'image' => $imagePath,
        ]);

        $this->resetInputFields();
        $this->properties = Property::all();
    }

    public function edit($id)
    {
        $property = Property::findOrFail($id);
        $this->propertyId = $property->id;
        $this->name = $property->name;
        $this->description = $property->description;
        $this->price_per_night = $property->price_per_night;
        $this->image = $property->image;
    }

    public function update()
    {
        $this->validate();
        $property = Property::find($this->propertyId);
        $imagePath = $this->image ? $this->image->store('images', 'public') : $property->image;

        //$property = Property::find($this->propertyId);
        $property->update([
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => $this->price_per_night,
            'image' => $imagePath,
        ]);

        $this->resetInputFields();
        $this->properties = Property::all();
    }

    public function delete($id)
    {
        Property::find($id)->delete();
        $this->properties = Property::all();
    }

    public function render()
    {
        return view('livewire.property-manager');
    }
}
