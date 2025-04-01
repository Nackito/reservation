<?php

namespace App\Filament\Resources\PropertiesResource\Pages;

use App\Filament\Resources\PropertiesResource;
use App\Models\PropertyImage;
use Filament\Resources\Pages\CreateRecord;

class CreateProperties extends CreateRecord
{
    protected static string $resource = PropertiesResource::class;

    protected function afterCreate(): void
    {
        $images = $this->data['images'] ?? []; // Récupère les images depuis le formulaire

        foreach ($images as $image) {
            PropertyImage::create([
                'property_id' => $this->record->id, // Associe l'image à la propriété créée
                'image_path' => $image, // Enregistre le chemin de l'image
            ]);
        }
    }
}
