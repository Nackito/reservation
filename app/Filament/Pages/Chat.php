<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Chat extends Page
{
    /*protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string $view = 'filament.pages.chat';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }*/
    // Désactive la navigation pour cette ressource (Filament 4)
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
