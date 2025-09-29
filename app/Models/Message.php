<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
  use HasFactory;

  protected $fillable = [
    'sender_id',
    'receiver_id',
    'content',
    'is_read',
    'conversation_id',
  ];

  public function sender()
  {
    return $this->belongsTo(User::class, 'sender_id');
  }

  public function receiver()
  {
    return $this->belongsTo(User::class, 'receiver_id');
  }

  /**
   * Accessor: contenu HTML sécurisé avec URLs cliquables et retours à la ligne.
   */
  public function getContentHtmlAttribute(): string
  {
    $text = (string) ($this->content ?? '');
    // Échapper d'abord pour prévenir le XSS
    $escaped = e($text);
    // Transformer les URLs en liens cliquables sans capturer la ponctuation finale
    $pattern = '/(https?:\/\/[^\s<]+)([\.,);!?:\]]?)(?=\s|$)/i';
    $linked = preg_replace_callback($pattern, function ($m) {
      $url = $m[1];
      $trail = $m[2] ?? '';
      $a = '<a href="' . $url . '" target="_blank" rel="noopener noreferrer" class="underline text-blue-600 dark:text-blue-400">' . $url . '</a>';
      return $a . $trail;
    }, $escaped);
    return nl2br($linked);
  }
}
