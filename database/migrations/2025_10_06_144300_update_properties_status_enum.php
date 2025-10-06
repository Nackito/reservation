<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  public function up(): void
  {
    $driver = DB::getDriverName();
    if ($driver === 'mysql') {
      // Normaliser quelques anciennes valeurs avant la modification de l'ENUM
      DB::table('properties')->where('status', 'booked')->update(['status' => 'rented']);
      DB::table('properties')->where('status', 'pending')->update(['status' => 'available']);

      // Modifier l'ENUM pour correspondre aux options Filament
      DB::statement("ALTER TABLE `properties` MODIFY `status` ENUM('available','rented','maintenance') NOT NULL DEFAULT 'available'");
    }
  }

  public function down(): void
  {
    $driver = DB::getDriverName();
    if ($driver === 'mysql') {
      // Revenir à l'ancien ENUM si besoin
      DB::statement("ALTER TABLE `properties` MODIFY `status` ENUM('available','booked','pending') NOT NULL DEFAULT 'available'");
    }
  }
};
