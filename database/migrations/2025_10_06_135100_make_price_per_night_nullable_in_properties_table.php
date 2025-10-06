<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    // Utilise du SQL brut pour éviter la dépendance doctrine/dbal lors d'un ALTER COLUMN.
    // MySQL uniquement; sur d'autres drivers (ex: sqlite), on ignore proprement.
    $driver = DB::getDriverName();
    if ($driver === 'mysql') {
      DB::statement('ALTER TABLE `properties` MODIFY `price_per_night` DECIMAL(8,2) NULL');
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // Revenir à NOT NULL (en posant une valeur par défaut à 0 si nécessaire) uniquement pour MySQL.
    $driver = DB::getDriverName();
    if ($driver === 'mysql') {
      DB::statement('ALTER TABLE `properties` MODIFY `price_per_night` DECIMAL(8,2) NOT NULL');
    }
  }
};
