<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('capacity')->default(1); // personnes max
            $table->unsignedTinyInteger('beds')->default(1);
            $table->decimal('price_per_night', 10, 2)->nullable(); // si null, fallback au prix de la propriété
            $table->unsignedInteger('inventory')->default(1); // nb de chambres de ce type
            $table->json('amenities')->nullable();
            $table->text('description')->nullable();
            $table->json('images')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
