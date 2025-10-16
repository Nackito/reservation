<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchState extends Model
{
  use HasFactory;

  protected $fillable = [
    'user_id',
    'session_id',
    'property_id',
    'date_range',
    'check_in',
    'check_out',
  ];
}
