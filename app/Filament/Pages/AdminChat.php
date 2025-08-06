<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AdminChat extends Page
{
  protected static ?string $navigationIcon = 'heroicon-o-chat-alt-2';
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
}
