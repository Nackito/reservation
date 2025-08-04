<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
  protected $fillable = [
    'user_id',
    'owner_id',
    'booking_id'
  ];

  public function messages(): HasMany
  {
    return $this->hasMany(Message::class);
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function owner(): BelongsTo
  {
    return $this->belongsTo(User::class, 'owner_id');
  }

  public function booking(): BelongsTo
  {
    return $this->belongsTo(Booking::class);
  }
}
