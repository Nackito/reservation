<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PreferencesController extends Controller
{
  // Page: choisir la devise
  public function currency()
  {
    $user = Auth::user();
    $current = $user?->currency ?? 'XOF';
    $currencies = config('currency.labels');
    return view('preferences.currency', compact('current', 'currencies'));
  }

  // POST: mise à jour de la devise
  public function updateCurrency(Request $request)
  {
    $supported = implode(',', config('currency.supported'));
    $validated = $request->validate([
      'currency' => "required|in:$supported",
    ]);

    $user = Auth::user();
    if ($user) {
      $user->currency = $validated['currency'];
      $user->save();
    } else {
      // Invité: demander la connexion pour enregistrer en base
      session(['url.intended' => url()->previous()]);
      return redirect()->route('login')
        ->with('error', "Connectez-vous pour enregistrer vos préférences.");
    }

    return back()->with('status', 'Votre devise a été mise à jour.');
  }

  // Page: choisir le thème d'affichage
  public function display()
  {
    // Préférence côté utilisateur si connecté, sinon cookie, sinon 'system'
    $user = Auth::user();
    $theme = $user?->theme ?? request()->cookie('theme', 'system');
    return view('preferences.display', compact('theme'));
  }

  // POST: mise à jour du thème (cookie + localStorage côté vue)
  public function updateDisplay(Request $request)
  {
    $validated = $request->validate([
      'theme' => 'required|in:light,dark,system',
    ]);

    // Sauvegarder sur le profil si connecté
    $user = Auth::user();
    if ($user) {
      $user->theme = $validated['theme'];
      // Si vous avez une colonne 'theme' sur users, ceci persistera
      try {
        $user->save();
      } catch (\Throwable $e) { /* ignore */
      }
    } else {
      // Invité: demander la connexion pour enregistrer en base
      session(['url.intended' => url()->previous()]);
      return redirect()->route('login')
        ->with('error', "Connectez-vous pour enregistrer vos préférences.");
    }

    return back()->with('status', 'Vos préférences d\'affichage ont été mises à jour.');
  }
}
