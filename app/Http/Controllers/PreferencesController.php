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
    $currencies = [
      'XOF' => 'Franc CFA (XOF)',
      'EUR' => 'Euro (EUR)',
      'USD' => 'US Dollar (USD)',
      'XAF' => 'Franc CFA (XAF)',
      'NGN' => 'Naira (NGN)',
      'GHS' => 'Ghana Cedi (GHS)',
    ];
    return view('preferences.currency', compact('current', 'currencies'));
  }

  // POST: mise à jour de la devise
  public function updateCurrency(Request $request)
  {
    $validated = $request->validate([
      'currency' => 'required|in:XOF,EUR,USD,XAF,NGN,GHS',
    ]);

    $user = Auth::user();
    if ($user) {
      $user->currency = $validated['currency'];
      $user->save();
    } else {
      // Utilisateur invité: garder la préférence côté cookie pour un usage invité
      return back()->with('status', 'Préférence enregistrée pour la session.')
        ->withCookie(cookie('currency', $validated['currency'], 60 * 24 * 365));
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
    }

    // Stocke aussi un cookie (fallback pour invités / cohérence multi-appareils)
    return back()->with('status', 'Vos préférences d\'affichage ont été mises à jour.')
      ->withCookie(cookie('theme', $validated['theme'], 60 * 24 * 365));
  }
}
