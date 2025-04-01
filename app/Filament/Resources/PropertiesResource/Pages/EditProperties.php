<?php

namespace App\Filament\Resources\PropertiesResource\Pages;

use App\Filament\Resources\PropertiesResource;
use App\Models\PropertyImage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProperties extends EditRecord
{
    protected static string $resource = PropertiesResource::class;

    protected function afterSave(): void
    {
        $newImages = $this->data['images'] ?? []; // Récupère les nouvelles images depuis le formulaire
        $existingImages = PropertyImage::where('property_id', $this->record->id)->pluck('image_path')->toArray();

        // Ajoute les nouvelles images qui n'existent pas encore
        foreach ($newImages as $image) {
            if (!in_array($image, $existingImages)) {
                PropertyImage::create([
                    'property_id' => $this->record->id,
                    'image_path' => $image,
                ]);
            }
        }

        // Supprime les images qui ne sont plus dans le formulaire
        foreach ($existingImages as $existingImage) {
            if (!in_array($existingImage, $newImages)) {
                PropertyImage::where('property_id', $this->record->id)
                    ->where('image_path', $existingImage)
                    ->delete();
            }
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
