<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
  public function show(Property $property)
  {
    // Charger les images et autres relations si besoin
    $property->load('images');
    // Charger d'autres relations si nécessaire (ex: owner, reviews...)
    return view('property.show', compact('property'));
  }
}
