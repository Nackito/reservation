<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up(): void
  {
    Schema::table('properties', function (Blueprint $table) {
      $table->decimal('latitude', 9, 6)->nullable()->after('price_per_night');
      $table->decimal('longitude', 9, 6)->nullable()->after('latitude');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('properties', function (Blueprint $table) {
      $table->dropColumn(['latitude', 'longitude']);
    });
  }
};
