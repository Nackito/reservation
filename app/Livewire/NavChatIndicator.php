<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class NavChatIndicator extends Component
{
  public int $unseen = 0;
  public string $variant = 'desktop'; // 'desktop' | 'mobile'
  public int $loginID;

  public function mount(string $variant = 'desktop')
  {
    $this->variant = in_array($variant, ['desktop', 'mobile'], true) ? $variant : 'desktop';
    $this->loginID = (int) Auth::id();
    $this->unseen = (int) session('nav_chat_unseen', 0);
  }

  public function getListeners()
  {
    return [
      "echo-private:chat.{$this->loginID},MessageSent" => 'onMessageSent',
      'resetNavChatUnseen' => 'resetUnseen',
    ];
  }

  public function onMessageSent(array $message): void
  {
    // N'incrémente que si l'expéditeur n'est pas moi (message entrant)
    if ((int) ($message['sender_id'] ?? 0) !== (int) $this->loginID) {
      $this->unseen++;
      session(['nav_chat_unseen' => $this->unseen]);
    }
  }

  public function resetUnseen(): void
  {
    $this->unseen = 0;
    session(['nav_chat_unseen' => 0]);
  }

  public function render()
  {
    return view('livewire.nav-chat-indicator');
  }
}
