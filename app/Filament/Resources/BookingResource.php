<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Mail;

use App\Notifications\BookingCanceledNotification;
use Illuminate\Support\Facades\Notification;
use App\Filament\Resources\BookingResource\Pages;
use App\Models\Booking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $recordTitleAttribute = 'id';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('property_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required(),
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->numeric(),
                Forms\Components\Select::make('status')
                    ->required()
                    ->options([
                        'pending' => 'Pending',
                        'accepted' => 'Accepted',
                        'canceled' => 'Canceled',
                    ]),
            ]);
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('accept')
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
                            $user->notify(new \App\Notifications\BookingAcceptedNotification($record));

                            // Envoi d'un mail personnalisé avec lien de paiement
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
                            Mail::raw($content, function ($message) use ($adminMail) {
                                $message->to($adminMail)
                                    ->subject('Réservation acceptée - Notification admin');
                            });
                        }

                        // Message système dans la conversation admin liée à la réservation avec lien de paiement
                        $conversation = \App\Models\Conversation::where('is_admin_channel', true)
                            ->where('booking_id', $record->id)
                            ->first();
                        if ($conversation) {
                            $msgContent = "Votre réservation a été acceptée, vous pouvez procéder au paiement.\n" .
                                "Montant à payer : $amount FrCFA\n" .
                                "Lien de paiement : $paymentUrl\n" .
                                "Sans paiement, nous ne pourrons vous garantir la disponibilité le jour-j.";
                            \App\Models\Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => 1, // 1 = admin ou système
                                'receiver_id' => $user ? $user->id : null,
                                'content' => $msgContent,
                            ]);
                        }
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn(Booking $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'canceled']);
                        $user = $record->user;
                        $admin = Auth::user();
                        if ($user) {
                            $user->notify(new \App\Notifications\BookingCanceledNotification($record));

                            // Envoi d'un mail personnalisé avec le même texte que le message système
                            Mail::raw(
                                "Votre demande de réservation a été annulée par l'administrateur.",
                                function ($message) use ($user) {
                                    $message->to($user->email)
                                        ->subject('Votre réservation a été annulée');
                                }
                            );
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
                            Mail::raw($content, function ($message) use ($adminMail) {
                                $message->to($adminMail)
                                    ->subject('Réservation annulée - Notification admin');
                            });
                        }

                        // Envoyer un message système dans la conversation admin liée à la réservation
                        $conversation = \App\Models\Conversation::where('is_admin_channel', true)
                            ->where('booking_id', $record->id)
                            ->first();
                        if ($conversation) {
                            \App\Models\Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => 1, // 1 = admin ou système, à adapter selon votre logique
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
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
