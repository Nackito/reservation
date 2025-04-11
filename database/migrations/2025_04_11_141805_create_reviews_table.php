<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Utilisateur ayant laissé l'avis
            $table->foreignId('property_id')->constrained()->onDelete('cascade'); // Propriété concernée
            $table->text('review'); // Contenu de l'avis
            $table->integer('rating')->unsigned(); // Note (1 à 5)
            $table->boolean('approved')->default(false); // Validation par l'admin
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
