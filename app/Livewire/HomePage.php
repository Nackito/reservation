<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Property;

#[Title('Home Page - Reservation')]
class HomePage extends Component
{
    //public $properties;

    /*public function mount()
    {
        $this->properties = Property::all();
    }

    public function render()
    {
        return view('livewire.home-page', [
            'properties' => $this->properties,
        ]);
    }*/
    public function render()
    {
        $properties = Property::all();
        return view('livewire.home-page', [
            'properties' => $properties, // Fetch all properties
        ]);
    }
}
