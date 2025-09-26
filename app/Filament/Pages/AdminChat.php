<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminChat extends Page
{
  //protected static ?string $navigationIcon = 'heroicon-o-chat-alt-2';
  protected static ?string $navigationLabel = 'Conversations';
  protected string $view = 'filament.pages.admin-chat';

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
        ->icon('heroicon-o-plus')
        ->form([
          Select::make('user_id')
            ->label('Utilisateur')
            ->searchable()
            ->options(fn() => User::query()
              ->whereNot('id', Auth::id())
              ->orderBy('name')
              ->pluck('name', 'id'))
            ->required(),
        ])
        ->action(function (array $data) {
          // Ouvrir la conversation avec l'utilisateur sélectionné
          $this->dispatch('openConversation', id: (string) $data['user_id'])
            ->to(\App\Livewire\AdminChatBox::class);
        }),

      Action::make('delete')
        ->label('Supprimer Chat')
        ->icon('heroicon-o-trash')
        ->requiresConfirmation()
        ->modalHeading('Supprimer la conversation courante ?')
        ->modalDescription("Cette action supprimera définitivement les messages de la conversation sélectionnée.")
        ->modalSubmitActionLabel('Supprimer')
        ->action(fn() => $this->dispatch('deleteCurrentConversation')->to(\App\Livewire\AdminChatBox::class)),
    ];
  }
}
