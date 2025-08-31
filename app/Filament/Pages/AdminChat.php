<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\Message;

class AdminChat extends Page
{
  /*protected static ?string $navigationIcon = 'heroicon-o-chat-alt-2';
  protected static ?string $navigationLabel = 'Conversations';

  protected static string $view = 'filament.pages.admin-chat';

  public static function canView(): bool
  {
    // Seulement admin
    return Auth::user()?->email === 'admin1@example.com';
  }
  public static function getNavigationItems(): array
  {
    return [
      \Filament\Navigation\NavigationItem::make()
        ->label('Conversations')
        ->icon('heroicon-o-chat-bubble-left-right')
        ->url(static::getUrl())
        ->isActiveWhen(fn(): bool => request()->routeIs(static::getRouteName())),
    ];
  }


  protected function getHeaderActions(): array
  {
    return [
      Action::make('create')
        ->label('Nouveau Chat')
        ->action(fn() => redirect()->route('admin.chat.create')),

      Action::make('delete')
        ->label('Supprimer Chat')
        ->requiresConfirmation()
        ->action(fn() => $this->deleteSelectedChat()),
    ];
  }

  private function deleteSelectedChat(): void
  {
    // Logique pour supprimer un chat sélectionné
    // Exemple : Message::where('id', $this->selectedChatId)->delete();
  }

  public function getMessagesProperty()
  {
    $userId = Auth::id();
    return Message::where(function ($q) use ($userId) {
      $q->where('sender_id', $userId)
        ->where('receiver_id', $this->selectedUserId);
    })->orWhere(function ($q) use ($userId) {
      $q->where('sender_id', $this->selectedUserId)
        ->where('receiver_id', $userId);
    })->orderBy('created_at')->get();
  }*/
}
