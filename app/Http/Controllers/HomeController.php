<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class HomeController extends Controller
{
  public function index()
  {
    $properties = Property::all();
    return view('home-page', compact('properties'));
  }
}
