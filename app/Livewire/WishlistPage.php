<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Wishlist;

class WishlistPage extends Component
{
  public function removeFromWishlist($wishlistId)
  {
    $wishlist = Wishlist::find($wishlistId);
    if ($wishlist && $wishlist->user_id == Auth::id()) {
      $wishlist->delete();
      session()->flash('message', 'Hébergement retiré de la liste de souhaits.');
    }
  }

  public function render()
  {
    $wishlists = Wishlist::with('property.images')
      ->where('user_id', Auth::id())
      ->get();
    return view('livewire.wishlist-page', [
      'wishlists' => $wishlists
    ]);
  }
}
