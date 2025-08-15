<?php

namespace App\Filament\Resources;

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
                    })
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn(Booking $record) => $record->status === 'pending'),
                Tables\Actions\Action::make('cancel')
                    ->label('Annuler')
                    ->action(function (Booking $record) {
                        $record->update(['status' => 'canceled']);
                        // Notifier l'utilisateur par email
                        $user = $record->user;
                        if ($user) {
                            $user->notify(new \App\Notifications\BookingCanceledNotification($record));
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
