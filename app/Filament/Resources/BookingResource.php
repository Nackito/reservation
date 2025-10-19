<?php

namespace App\Filament\Resources;

use Illuminate\Support\Facades\Mail;
use App\Services\CinetPayService;
use Illuminate\Support\Facades\Log;
use App\Services\Admin\BookingActionHelper;

use App\Notifications\BookingCanceledNotification;
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
// use Illuminate\Support\Facades\Log; // duplicate removed
use Filament\Support\Enums\IconName;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
// use Filament\Notifications\Notification;
//use App\Filament\Resources\BookingResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
//use Filament\Forms\Components\TextInput;
//use Illuminate\Support\Facades\Mail;
//use App\Models\Booking;
use Filament\Resources\Pages\CreateRecord;
use BackedEnum;
use Filament\Notifications\Notification;
use App\Models\Payment;

class BookingResource extends Resource
{
    private const DATE_FMT = 'd/m/Y';
    private const SIM_SUFFIX = '-sim-';
    protected static ?string $model = Booking::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
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
            ->columns(self::tableColumns())
            ->filters([
                //
            ])
            ->actions(self::tableActions())
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Colonnes de la table Filament
     */
    private static function tableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('property.name')
                ->label('Property')
                ->sortable(),
            Tables\Columns\TextColumn::make('roomType.name')
                ->label('Room Type')
                ->sortable(),
            Tables\Columns\TextColumn::make('user.name')
                ->label('Requested by')
                ->sortable(),
            Tables\Columns\TextColumn::make('start_date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('end_date')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('total_price')
                ->numeric()
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->searchable(),
            Tables\Columns\BadgeColumn::make('payment_status')
                ->label('Paiement')
                ->colors([
                    'warning' => 'pending',
                    'success' => 'paid',
                    'danger' => 'failed',
                ])
                ->icons([
                    'heroicon-o-clock' => 'pending',
                    'heroicon-o-check-circle' => 'paid',
                    'heroicon-o-x-circle' => 'failed',
                ])
                ->sortable(),
            Tables\Columns\TextColumn::make('created_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('updated_at')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            Tables\Columns\TextColumn::make('payment_pending_since')
                ->label('')
                ->getStateUsing(fn($record) => $record ? \Carbon\Carbon::parse($record->updated_at ?? $record->created_at)->diffForHumans() : null)
                ->formatStateUsing(fn($state) => $state ? 'En attente de paiement depuis ' . $state : null)
                ->visible(fn($record) => $record && ($record->payment_status ?? null) === 'pending')
                ->badge()
                ->color('warning'),
            Tables\Columns\TextColumn::make('paid_at')
                ->label('')
                ->visible(fn($record) => $record && ($record->payment_status ?? null) === 'paid' && !empty($record->paid_at))
                ->formatStateUsing(function ($state) {
                    if (!$state) {
                        return null;
                    }
                    $date = $state instanceof \Carbon\Carbon ? $state : \Carbon\Carbon::parse($state);
                    return 'Payée le ' . $date->format(self::DATE_FMT);
                })
                ->badge()
                ->color('success'),
        ];
    }

    /**
     * Actions de ligne (Filament)
     */
    private static function tableActions(): array
    {
        return [
            EditAction::make(),
            self::actionAccept(),
            self::actionCancel(),
            self::actionSimulateSuccess(),
            self::actionSimulateFail(),
            self::actionSimulateCancel(),
        ];
    }

    // Actions de simulation retirées

    private static function actionSimulateSuccess(): Action
    {
        return Action::make('simulate_success')
            ->label('Simuler paiement OK')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn($record) => config('cinetpay.simulation_enabled') && $record && (($record->payment_status ?? 'pending') !== 'paid'))
            ->action(function (Booking $record) {
                $txId = $record->payment_transaction_id ?: ('BK-' . $record->id . self::SIM_SUFFIX . now()->timestamp);
                // Marquer payé si pas déjà fait
                if (($record->payment_status ?? 'pending') !== 'paid') {
                    $record->markAsPaid($txId);
                    try {
                        BookingActionHelper::handlePaymentConflictsForOthers($record);
                    } catch (\Throwable $e) {
                        Log::warning('Conflit de paiements (simulate_success) non traité', ['err' => $e->getMessage()]);
                    }
                    // Message dans la conversation + emails de confirmation
                    try {
                        $amountFmt = is_numeric($record->total_price) ? number_format($record->total_price, 0, ',', ' ') : (string)$record->total_price;
                        $conversation = \App\Models\Conversation::where('is_admin_channel', true)
                            ->where('booking_id', $record->id)
                            ->first();
                        if ($conversation) {
                            $msg = "Paiement confirmé (simulation). Nous avons bien reçu {$amountFmt} FrCFA. Réf: {$txId}";
                            $message = \App\Models\Message::create([
                                'conversation_id' => $conversation->id,
                                'sender_id' => Auth::id() ?: 1,
                                'receiver_id' => $record->user?->id,
                                'content' => $msg,
                            ]);
                            try {
                                broadcast(new \App\Events\MessageSent($message));
                            } catch (\Throwable $e) { /* ignore */
                            }
                        }
                        $user = $record->user;
                        if ($user && $user->email) {
                            Mail::raw(
                                "Votre paiement (simulation) a été confirmé. Montant: {$amountFmt} FrCFA. Référence: {$txId}.",
                                function ($m) use ($user, $record) {
                                    $m->to($user->email)->subject('Paiement confirmé (simulation) - Réservation #' . $record->id);
                                }
                            );
                        }
                        $adminMail = config('mail.admin_email') ?? env('MAIL_ADMIN_EMAIL');
                        if ($adminMail) {
                            Mail::raw(
                                'Paiement simulé confirmé pour la réservation #' . $record->id . ' (tx: ' . $txId . ')',
                                function ($m) use ($adminMail) {
                                    $m->to($adminMail)->subject('Paiement confirmé (simulation) - Réservation');
                                }
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Post-simulate_success notifications non envoyées', ['err' => $e->getMessage()]);
                    }
                }
                // Audit
                try {
                    Payment::create([
                        'booking_id' => $record->id,
                        'transaction_id' => $txId,
                        'status' => 'SIMULATED_SUCCESS',
                        'source' => 'admin',
                        'signature_valid' => null,
                        'payload' => ['by' => Auth::id()],
                        'headers' => null,
                        'ip' => request()->ip(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Payment audit (simulate_success) échoué', ['err' => $e->getMessage()]);
                }
                Notification::make()
                    ->title('Paiement simulé avec succès')
                    ->success()
                    ->send();
            });
    }

    private static function actionSimulateFail(): Action
    {
        return Action::make('simulate_fail')
            ->label('Simuler paiement échoué')
            ->icon('heroicon-m-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn($record) => config('cinetpay.simulation_enabled') && $record && (($record->payment_status ?? 'pending') !== 'paid'))
            ->action(function (Booking $record) {
                $txId = $record->payment_transaction_id ?: ('BK-' . $record->id . self::SIM_SUFFIX . now()->timestamp);
                $record->payment_status = 'failed';
                $record->save();
                // Audit
                try {
                    Payment::create([
                        'booking_id' => $record->id,
                        'transaction_id' => $txId,
                        'status' => 'SIMULATED_FAILED',
                        'source' => 'admin',
                        'signature_valid' => null,
                        'payload' => ['by' => Auth::id()],
                        'headers' => null,
                        'ip' => request()->ip(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Payment audit (simulate_fail) échoué', ['err' => $e->getMessage()]);
                }
                Notification::make()
                    ->title("Statut de paiement marqué 'échoué'")
                    ->danger()
                    ->send();
            });
    }

    private static function actionSimulateCancel(): Action
    {
        return Action::make('simulate_cancel')
            ->label('Simuler annulation')
            ->icon('heroicon-m-arrow-uturn-left')
            ->color('warning')
            ->requiresConfirmation()
            ->visible(fn($record) => config('cinetpay.simulation_enabled') && $record)
            ->action(function (Booking $record) {
                $txId = $record->payment_transaction_id ?: ('BK-' . $record->id . self::SIM_SUFFIX . now()->timestamp);
                // Ne change pas le statut, juste audit
                try {
                    Payment::create([
                        'booking_id' => $record->id,
                        'transaction_id' => $txId,
                        'status' => 'SIMULATED_CANCELED',
                        'source' => 'admin',
                        'signature_valid' => null,
                        'payload' => ['by' => Auth::id()],
                        'headers' => null,
                        'ip' => request()->ip(),
                    ]);
                } catch (\Throwable $e) {
                    Log::warning('Payment audit (simulate_cancel) échoué', ['err' => $e->getMessage()]);
                }
                Notification::make()
                    ->title('Annulation simulée enregistrée')
                    ->warning()
                    ->send();
            });
    }

    private static function actionAccept(): Action
    {
        return Action::make('accept')
            ->label('Accepter')
            ->action(function (Booking $record) {
                BookingActionHelper::handleAcceptAction($record);
            })
            ->requiresConfirmation()
            ->color('success')
            ->visible(fn($record) => $record && $record->status === 'pending');
    }

    private static function actionCancel(): Action
    {
        return Action::make('cancel')
            ->label('Annuler')
            ->action(function (Booking $record) {
                BookingActionHelper::handleCancelAction($record);
            })
            ->requiresConfirmation()
            ->color('danger')
            ->visible(fn($record) => $record && $record->status === 'pending');
    }

    // Helpers déplacés dans App\Services\Admin\BookingActionHelper

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
