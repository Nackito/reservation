<?php

namespace App\Filament\Resources\BookingResource\Pages;

use App\Filament\Resources\BookingResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Mail;
use App\Models\Booking;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    protected static string $resource = BookingResource::class;


    protected function afterCreate(Booking $record): void
    {
        // Exemple : envoi d'un mail à l'utilisateur
        if ($record->user && $record->user->email) {
            Mail::raw(
                "Votre réservation a bien été créée. Merci !",
                function ($message) use ($record) {
                    $message->to($record->user->email)
                        ->subject('Confirmation de réservation');
                }
            );
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Réservation créée avec succès !';
    }

    protected function getRedirectUrl(): string
    {
        // Redirige vers la page d’édition de la réservation nouvellement créée
        return static::$resource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
