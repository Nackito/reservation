<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      //$table->string('firstname')->nullable()->after('name')->change();
      $table->string('country_code')->nullable()->after('phone')->change();
    });
  }

  public function down(): void
  {
    // Impossible de revenir à l'ordre précédent automatiquement
  }
};
