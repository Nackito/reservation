<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;

class WishlistController extends Controller
{
  public function index()
  {
    $wishlists = Wishlist::with('property.images')
      ->where('user_id', Auth::id())
      ->get();
    return view('wishlist.index', compact('wishlists'));
  }
}
