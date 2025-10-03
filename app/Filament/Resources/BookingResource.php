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

class BookingResource extends Resource
{
    private const DATE_FMT = 'd/m/Y';
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
        ];
    }

    // Actions de simulation retirées

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
