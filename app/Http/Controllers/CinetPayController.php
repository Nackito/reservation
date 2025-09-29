<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CinetPayController extends Controller
{
  public function notify(Request $request)
  {
    // CinetPay envoie un POST avec les informations de transaction
    Log::info('CinetPay notify payload', ['payload' => $request->all()]);
    // Vérifier la signature/transaction si nécessaire et mettre à jour le statut (paid) de la réservation
    return response()->json(['status' => 'ok']);
  }

  public function return(Request $request)
  {
    // L'utilisateur est redirigé ici après le paiement
    // Vous pouvez afficher une page de succès/échec en fonction des paramètres
    return redirect()->route('user-reservations')->with('status', 'Paiement CinetPay traité.');
  }
}
