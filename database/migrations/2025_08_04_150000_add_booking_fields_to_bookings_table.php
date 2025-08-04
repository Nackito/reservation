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
    Schema::table('bookings', function (Blueprint $table) {
      if (!Schema::hasColumn('bookings', 'start_date')) {
        $table->date('start_date')->nullable()->after('user_id');
      }
      if (!Schema::hasColumn('bookings', 'end_date')) {
        $table->date('end_date')->nullable()->after('start_date');
      }
      if (!Schema::hasColumn('bookings', 'total_price')) {
        $table->integer('total_price')->nullable()->after('end_date');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('bookings', function (Blueprint $table) {
      if (Schema::hasColumn('bookings', 'start_date')) {
        $table->dropColumn('start_date');
      }
      if (Schema::hasColumn('bookings', 'end_date')) {
        $table->dropColumn('end_date');
      }
      if (Schema::hasColumn('bookings', 'total_price')) {
        $table->dropColumn('total_price');
      }
    });
  }
};
