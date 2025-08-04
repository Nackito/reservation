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
      if (!Schema::hasColumn('bookings', 'user_id')) {
        $table->unsignedBigInteger('user_id')->nullable()->after('property_id');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      }
      if (!Schema::hasColumn('bookings', 'start_date')) {
        $table->date('start_date')->nullable()->after('user_id');
      }
      if (!Schema::hasColumn('bookings', 'end_date')) {
        $table->date('end_date')->nullable()->after('start_date');
      }
      if (!Schema::hasColumn('bookings', 'total_price')) {
        $table->integer('total_price')->nullable()->after('end_date');
      }
      if (!Schema::hasColumn('bookings', 'updated_at')) {
        $table->timestamp('updated_at')->nullable()->after('total_price');
      }
      if (!Schema::hasColumn('bookings', 'created_at')) {
        $table->timestamp('created_at')->nullable()->after('updated_at');
      }
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('bookings', function (Blueprint $table) {
      if (Schema::hasColumn('bookings', 'user_id')) {
        $table->dropForeign(['user_id']);
        $table->dropColumn('user_id');
      }
      if (Schema::hasColumn('bookings', 'start_date')) {
        $table->dropColumn('start_date');
      }
      if (Schema::hasColumn('bookings', 'end_date')) {
        $table->dropColumn('end_date');
      }
      if (Schema::hasColumn('bookings', 'total_price')) {
        $table->dropColumn('total_price');
      }
      if (Schema::hasColumn('bookings', 'updated_at')) {
        $table->dropColumn('updated_at');
      }
      if (Schema::hasColumn('bookings', 'created_at')) {
        $table->dropColumn('created_at');
      }
    });
  }
};
