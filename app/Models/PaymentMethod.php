<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'brand',      // ex: visa, mastercard
    'last4',      // 4 derniers chiffres
    'exp_month',  // 1-12
    'exp_year',   // YYYY
    'token',      // jeton PSP (optionnel)
    'is_default', // bool
  ];

  protected $casts = [
    'is_default' => 'boolean',
  ];

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
