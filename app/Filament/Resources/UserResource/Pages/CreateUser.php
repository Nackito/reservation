<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        parent::afterCreate();
        if ($this->record && !$this->record->hasVerifiedEmail()) {
            $this->record->sendEmailVerificationNotification();
            $this->notify('success', 'Un email de vérification a été envoyé à l’utilisateur.');
        }
    }
}
