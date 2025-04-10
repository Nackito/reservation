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
        Schema::table('properties', function (Blueprint $table) {
            $table->string('property_type')->nullable()->after('municipality'); // Type de propriété
            $table->integer('number_of_rooms')->nullable()->after('property_type'); // Nombre de pièces
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn('property_type'); // Supprime la colonne property_type
            $table->dropColumn('number_of_rooms'); // Supprime la colonne number_of_rooms
        });
    }
};
