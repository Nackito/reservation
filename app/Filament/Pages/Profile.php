<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class Profile extends Page implements HasForms
{
  use InteractsWithForms;

  protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user';
  protected static ?string $title = 'Mon profil';
  protected bool $hasLogo = false;
  protected string $view = 'filament.pages.profile';

  public ?array $data = [];

  public function mount(): void
  {
    $u = Auth::user();
    $this->form->fill([
      'name' => $u?->name,
      'email' => $u?->email,
      'password' => '',
      'password_confirmation' => '',
    ]);
  }

  public function form(Schema $schema): Schema
  {
    return $schema
      ->schema([
        TextInput::make('name')->label('Nom')->required()->maxLength(255),
        TextInput::make('firstname')->label('Prénom')->required()->maxLength(255),
        TextInput::make('email')->label('Email')->email()->required(),
        TextInput::make('password')->label('Nouveau mot de passe')->password()->revealable()->minLength(8)->nullable(),
        TextInput::make('password_confirmation')->label('Confirmer le mot de passe')->password()->revealable()->nullable(),
      ])
      ->statePath('data');
  }

  public function save(): void
  {
    /** @var \App\Models\User|null $user */
    $user = Auth::user();
    if (!$user) {
      abort(403);
    }

    $data = $this->form->getState();

    $this->validate([
      'data.name' => ['required', 'string', 'max:255'],
      'data.email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
      'data.password' => ['nullable', 'min:8', 'same:data.password_confirmation'],
    ]);

    $user->name = $data['name'] ?? $user->name;
    $user->email = $data['email'] ?? $user->email;
    if (!empty($data['password'])) {
      $user->password = Hash::make($data['password']);
    }
    $user->save();

    Notification::make()->title('Profil mis à jour')->success()->send();

    $this->form->fill(['password' => '', 'password_confirmation' => '']);
  }
}
