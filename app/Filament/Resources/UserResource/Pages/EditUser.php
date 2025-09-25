<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('resend_verification_email')
                ->label('Renvoyer l’email de vérification')
                ->icon('heroicon-o-envelope')
                ->visible(fn() => $this->record && !$this->record->hasVerifiedEmail())
                ->action(function () {
                    $this->record->sendEmailVerificationNotification();
                    $this->notify('success', 'Email de vérification renvoyé à l’utilisateur.');
                }),
        ];
    }

    protected function saved(): void
    {
        // Si l'email a changé et n'est pas vérifié, renvoyer la notification
        if ($this->record && !$this->record->hasVerifiedEmail()) {
            $this->record->sendEmailVerificationNotification();
            $this->notify('success', 'Un email de vérification a été envoyé à l’utilisateur.');
        }
    }
}
