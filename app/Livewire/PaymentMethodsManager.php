<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PaymentMethod;
use App\Services\PaymentTokenService;

class PaymentMethodsManager extends Component
{
  public $methods = [];
  public $brand = '';
  public $cardholder = '';
  public $number = '';
  public $exp = '';

  // Règles dynamiques: utiliser une méthode plutôt qu'une propriété (évite les expressions non constantes)
  public function rules(): array
  {
    $currentYear = (int) date('Y');
    return [
      'cardholder' => 'required|string|min:2|max:100',
      'number' => 'required|digits_between:12,19',
      'exp' => 'required|string', // sera parsé MM/AA dans add()
    ];
  }

  public function mount()
  {
    $this->reload();
  }

  public function reload()
  {
    $this->methods = PaymentMethod::where('user_id', Auth::id())->orderByDesc('is_default')->orderByDesc('id')->get();
  }

  public function add()
  {
    $data = $this->validate();
    // Sanitize number et extraire last4
    $digits = preg_replace('/\D+/', '', (string) $this->number);
    // Vérification Luhn côté serveur
    if (!$this->luhnCheck($digits)) {
      $this->addError('number', "Numéro de carte invalide (échec Luhn).");
      return;
    }
    $last4 = substr($digits, -4);
    // Parser exp en MM/AA ou MM/AAAA
    $month = null;
    $year = null;
    if (preg_match('/^\s*(\d{1,2})\s*[\/\s]\s*(\d{2,4})\s*$/', (string) $this->exp, $m)) {
      $month = (int) $m[1];
      $yy = (int) $m[2];
      $year = $yy < 100 ? (2000 + $yy) : $yy;
    }
    $currentYear = (int) date('Y');
    if (!$month || $month < 1 || $month > 12 || !$year || $year < $currentYear || $year > ($currentYear + 15)) {
      $this->addError('exp', "Date d'expiration invalide.");
      return;
    }
    // Détecter la marque de façon heuristique (simple)
    $brand = $this->detectBrand($digits);

    // Tokenisation via service PSP (squelette)
    $tokenService = new PaymentTokenService();
    $tokenResult = $tokenService->tokenizeCard([
      'number' => $digits,
      'exp_month' => $month,
      'exp_year' => $year,
      'cardholder' => $this->cardholder,
    ]);

    DB::transaction(function () use ($brand, $last4, $month, $year, $tokenResult) {
      // Si aucune carte existe, la nouvelle devient par défaut
      $isDefault = empty($this->methods);
      PaymentMethod::create([
        'user_id' => Auth::id(),
        'brand' => $tokenResult['brand'] ?? $brand,
        'last4' => $tokenResult['last4'] ?? $last4,
        'exp_month' => $month,
        'exp_year' => $year,
        'token' => $tokenResult['token'] ?? null,
        'is_default' => $isDefault,
      ]);
    });
    $this->reset(['brand', 'cardholder', 'number', 'exp']);
    $this->reload();
    session()->flash('status', 'Carte ajoutée.');
  }

  public function setDefault($id)
  {
    $pm = PaymentMethod::where('user_id', Auth::id())->where('id', $id)->first();
    if (!$pm) return;
    DB::transaction(function () use ($pm) {
      PaymentMethod::where('user_id', Auth::id())->update(['is_default' => false]);
      $pm->is_default = true;
      $pm->save();
    });
    $this->reload();
    session()->flash('status', 'Carte définie par défaut.');
  }

  protected function detectBrand(string $digits): string
  {
    if (preg_match('/^4\d{6,}$/', $digits)) return 'visa';
    if (preg_match('/^5[1-5]\d{5,}$/', $digits)) return 'mastercard';
    if (preg_match('/^3[47]\d{5,}$/', $digits)) return 'amex';
    if (preg_match('/^6(?:011|5)\d{4,}$/', $digits)) return 'discover';
    if (preg_match('/^35\d{4,}$/', $digits)) return 'jcb';
    if (preg_match('/^(?:50|5[0678]|6\d)\d{4,}$/', $digits)) return 'maestro';
    if (preg_match('/^3(?:0[0-5]|[68])\d{4,}$/', $digits)) return 'diners';
    return 'card';
  }

  public function remove($id)
  {
    $pm = PaymentMethod::where('user_id', Auth::id())->where('id', $id)->first();
    if ($pm) {
      $pm->delete();
      $this->reload();
      session()->flash('status', 'Carte supprimée.');
    }
  }

  protected function luhnCheck(string $digits): bool
  {
    $sum = 0;
    $len = strlen($digits);
    for ($i = $len - 1, $pos = 0; $i >= 0; $i--, $pos++) {
      $digit = (int) $digits[$i];
      if ($pos % 2 === 1) {
        $digit *= 2;
        if ($digit > 9) $digit -= 9;
      }
      $sum += $digit;
    }
    return ($sum % 10) === 0;
  }


  public function render()
  {
    return view('livewire.payment-methods-manager');
  }
}
