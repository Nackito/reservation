<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChatBox extends Component
{
    public $newMessage = '';
    public $receiverId;

  protected $rules = [
    'newMessage' => 'required|string',
  ];

    public function mount($receiverId = null)
    {
        // Si aucun destinataire spécifié, utiliser l'admin (par email)
        if (! $receiverId) {
            $admin = User::where('email', 'admin1@example.com')->first();
            $this->receiverId = $admin?->id;
        } else {
            $this->receiverId = $receiverId;
        }
    }

    // Messages chargés à chaque rendu

    public function sendMessage()
    {
        $this->validate();
        Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $this->receiverId,
            'content' => $this->newMessage,
        ]);
        $this->newMessage = '';
        // Event si besoin
    }

    public function render()
    {
        $userId = Auth::id();
        $messages = Message::where(function($q) use ($userId) {
            $q->where('sender_id', $userId)->where('receiver_id', $this->receiverId);
        })->orWhere(function($q) use ($userId) {
            $q->where('sender_id', $this->receiverId)->where('receiver_id', $userId);
        })->orderBy('created_at')->get();
        return view('livewire.chat-box', compact('messages'));
    }
}
