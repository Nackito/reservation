<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
  use HasFactory;

  protected $fillable = [
    'booking_id',
    'transaction_id',
    'status',
    'source',
    'signature_valid',
    'payload',
    'headers',
    'ip',
  ];

  protected $casts = [
    'payload' => 'array',
    'headers' => 'array',
    'signature_valid' => 'boolean',
  ];

  public function booking()
  {
    return $this->belongsTo(Booking::class);
  }
}
