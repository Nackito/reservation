<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Mail;

use App\Notifications\BookingCanceledNotification;
use Illuminate\Support\Facades\Notification;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Filament\Support\Enums\IconName;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
//use App\Filament\Resources\BookingResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
//use Filament\Forms\Components\TextInput;
//use Illuminate\Support\Facades\Mail;
//use App\Models\Booking;
use Filament\Resources\Pages\CreateRecord;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 3;
    public static function getRecordTitle($record): ?string
    {
        return $record->property->name ?? 'Réservation';
    }

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([
            Select::make('property_id')
                ->relationship('property', 'name')
                ->required(),
            Select::make('user_id')
                ->relationship('user', 'name')
                ->required(),
            DatePicker::make('start_date')
                ->required(),
            DatePicker::make('end_date')
                ->required(),
            Select::make('status')
                ->options([
                    'pending' => 'En attente',
                    'accepted' => 'Acceptée',
                    'canceled' => 'Annulée',
                ])
                ->required(),
        ]);
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Réservation créée avec succès !';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('property.name')
                    ->label('Propriétés')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Demande emise par')
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                Action::make('accept')
                    ->label('Accepter')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'accepted']);
                        $user = $record->user;
                        $admin = Auth::user();
                        // Générer le lien de paiement (placeholder, à remplacer par la vraie route plus tard)
                        $paymentUrl = url('/payment/cinetpay/' . $record->id);
                        $amount = method_exists($record, 'calculateTotalPrice') ? $record->calculateTotalPrice() : $record->total_price;
                        if ($user) {
                            // Notification Laravel (mail + database)
                            try {
                                $user->notify(new \App\Notifications\BookingAcceptedNotification($record));
                            } catch (\Throwable $e) {
                                Log::warning('Notification acceptation non envoyée: ' . $e->getMessage());
                            }

                            // Envoi d'un mail personnalisé avec lien de paiement (protégé contre les erreurs SMTP)
                            try {
                                $mailContent = "Votre réservation a été acceptée, vous pouvez procéder au paiement en cliquant sur le lien ci-dessous.\n\n" .
                                    "Montant à payer : $amount FrCFA\n" .
                                    "Lien de paiement : $paymentUrl\n\n" .
                                    "Sans paiement, nous ne pourrons vous garantir la disponibilité le jour-j.";
                                Mail::raw(
                                    $mailContent,
                                    function ($message) use ($user) {
                                        $message->to($user->email)
                                            ->subject('Votre réservation a été acceptée');
                                    }
                                );
                            } catch (\Throwable $e) {
                                Log::warning('Email acceptation réservation non envoyé (rate-limit ou SMTP): ' . $e->getMessage());
                            }
                        }

                        // Envoi d'un mail à l'admin avec les infos de la réservation
                        $adminMail = $admin ? $admin->email : null;
                        if ($adminMail) {
                            $propertyName = $record->property->name ?? '';
                            $userName = $user ? $user->name : '';
                            $startDate = $record->start_date;
                            $endDate = $record->end_date;
                            $createdAt = $record->created_at;
                            $adminName = $admin->name ?? '';
                            $content = "Réservation acceptée :\n" .
                                "- Propriété : $propertyName\n" .
                                "- Utilisateur : $userName\n" .
                                "- Date d'entrée : $startDate\n" .
                                "- Date de sortie : $endDate\n" .
                                "- Date de soumission : $createdAt\n" .
                                "- Action réalisée par : $adminName";
                            try {
                                Mail::raw($content, function ($message) use ($adminMail) {
                                    $message->to($adminMail)
                                        ->subject('Réservation acceptée - Notification admin');
                                });
                            } catch (\Throwable $e) {
                                Log::warning('Email admin acceptation non envoyé: ' . $e->getMessage());
                            }
                        }

                        // Message système dans la conversation admin liée à la réservation avec lien de paiement
                        $conversation = \App\Models\Conversation::where('is_admin_channel', true)
                            ->where('booking_id', $record->id)
                            ->first();
                        if ($conversation) {
                            // Version "Standard, clair et professionnel"
                            $propertyName = $record->property->name ?? 'votre hébergement';
                            $start = $record->start_date ? \Carbon\Carbon::parse($record->start_date)->format('d/m/Y') : '';
                            $end = $record->end_date ? \Carbon\Carbon::parse($record->end_date)->format('d/m/Y') : '';
                            $amountFmt = is_numeric($amount) ? number_format($amount, 0, ',', ' ') : (string)$amount;
                            $msgContent = "Votre réservation pour {$propertyName} du {$start} au {$end} a été acceptée.\n" .
                                "Montant à régler : {$amountFmt} FrCFA.\n" .
                                "Veuillez procéder au paiement via ce lien sécurisé : {$paymentUrl}.\n" .
                                "Sans règlement sous 24h, la disponibilité ne peut être garantie.";
                            $message = \App\Models\Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => $admin ? $admin->id : 1,
                                'receiver_id' => $user ? $user->id : null,
                                'content' => $msgContent,
                            ]);
                            // Diffuser en temps réel pour l'utilisateur (canal chat.{receiver_id})
                            if ($user) {
                                try {
                                    broadcast(new \App\Events\MessageSent($message));
                                } catch (\Throwable $e) {
                                    // Ignorer si broadcasting non configuré
                                }
                            }
                        }
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn(Booking $record) => $record->status === 'pending'),
                Action::make('cancel')
                    ->label('Annuler')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'canceled']);
                        $user = $record->user;
                        $admin = Auth::user();
                        if ($user) {
                            try {
                                $user->notify(new \App\Notifications\BookingCanceledNotification($record));
                            } catch (\Throwable $e) {
                                Log::warning('Notification annulation non envoyée: ' . $e->getMessage());
                            }

                            // Envoi d'un mail personnalisé avec le même texte que le message système (protégé)
                            try {
                                Mail::raw(
                                    "Votre demande de réservation a été annulée par l'administrateur.",
                                    function ($message) use ($user) {
                                        $message->to($user->email)
                                            ->subject('Votre réservation a été annulée');
                                    }
                                );
                            } catch (\Throwable $e) {
                                Log::warning('Email annulation utilisateur non envoyé: ' . $e->getMessage());
                            }
                        }

                        // Envoi d'un mail à l'admin avec les infos de la réservation
                        $adminMail = $admin ? $admin->email : null;
                        if ($adminMail) {
                            $propertyName = $record->property->name ?? '';
                            $userName = $user ? $user->name : '';
                            $startDate = $record->start_date;
                            $endDate = $record->end_date;
                            $createdAt = $record->created_at;
                            $adminName = $admin->name ?? '';
                            $content = "Réservation annulée :\n" .
                                "- Propriété : $propertyName\n" .
                                "- Utilisateur : $userName\n" .
                                "- Date d'entrée : $startDate\n" .
                                "- Date de sortie : $endDate\n" .
                                "- Date de soumission : $createdAt\n" .
                                "- Action réalisée par : $adminName";
                            try {
                                Mail::raw($content, function ($message) use ($adminMail) {
                                    $message->to($adminMail)
                                        ->subject('Réservation annulée - Notification admin');
                                });
                            } catch (\Throwable $e) {
                                Log::warning('Email annulation admin non envoyé: ' . $e->getMessage());
                            }
                        }

                        // Envoyer un message système dans la conversation admin liée à la réservation
                        $conversation = \App\Models\Conversation::where('is_admin_channel', true)
                            ->where('booking_id', $record->id)
                            ->first();
                        if ($conversation) {
                            \App\Models\Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => 1,
                                'receiver_id' => $user ? $user->id : null,
                                'content' => "Votre demande de réservation a été annulée par l'administrateur.",
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn(Booking $record) => $record->status === 'pending'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // Tri par défaut : du plus récent au plus ancien
        return parent::getEloquentQuery()->orderByDesc('created_at');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
