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
            $table->enum('status', ['available', 'booked', 'pending'])->default('available')->after('district'); // Ajoute la colonne 'status' après 'district'
            $table->string('slug')->after('name'); // Ajoute la colonne 'slug' après 'name'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->dropColumn(['status', 'slug']);
        });
    }
};
