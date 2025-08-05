<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MessageResource\Pages;
use App\Models\Message;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;

class MessageResource extends Resource
{
  protected static ?string $model = Message::class;

  protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left';
  protected static ?string $navigationLabel = 'Messages';
  protected static ?string $pluralLabel = 'Messages';
  protected static ?string $slug = 'messages';

  public static function form(Forms\Form $form): Forms\Form
  {
    return $form
      ->schema([
        Forms\Components\TextInput::make('sender_id')->required(),
        Forms\Components\TextInput::make('receiver_id')->required(),
        Forms\Components\Textarea::make('content')->required(),
      ]);
  }

  public static function table(Table $table): Table
  {
    return $table
      ->columns([
        Tables\Columns\TextColumn::make('id')->sortable(),
        Tables\Columns\TextColumn::make('sender_id'),
        Tables\Columns\TextColumn::make('receiver_id'),
        Tables\Columns\TextColumn::make('content')->limit(50),
        Tables\Columns\TextColumn::make('created_at')->dateTime(),
      ])
      ->filters([
        // Ajoutez des filtres si besoin
      ]);
  }

  public static function getPages(): array
  {
    return [
      'index' => Pages\ListMessages::route('/'),
      'create' => Pages\CreateMessage::route('/create'),
      'edit' => Pages\EditMessage::route('/{record}/edit'),
    ];
  }
}
