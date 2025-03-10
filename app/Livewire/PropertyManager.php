<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Property;
use Livewire\WithPagination;

class PropertyManager extends Component
{
    public $properties;
    public $name;
    public $description;
    public $price_per_night;
    public $propertyId;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'required|string',
        'price_per_night' => 'required|numeric',
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
    }

    public function store()
    {
        $this->validate();

        Property::create([
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => $this->price_per_night,
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
    }

    public function update()
    {
        $this->validate();

        $property = Property::find($this->propertyId);
        $property->update([
            'name' => $this->name,
            'description' => $this->description,
            'price_per_night' => $this->price_per_night,
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
