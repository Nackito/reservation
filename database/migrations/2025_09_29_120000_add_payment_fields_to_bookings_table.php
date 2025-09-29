<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('bookings', function (Blueprint $table) {
      if (!Schema::hasColumn('bookings', 'payment_transaction_id')) {
        $table->string('payment_transaction_id')->nullable()->index();
      }
      if (!Schema::hasColumn('bookings', 'payment_status')) {
        $table->string('payment_status')->default('pending'); // pending|paid|failed|canceled
      }
      if (!Schema::hasColumn('bookings', 'paid_at')) {
        $table->timestamp('paid_at')->nullable();
      }
    });
  }

  public function down(): void
  {
    Schema::table('bookings', function (Blueprint $table) {
      if (Schema::hasColumn('bookings', 'payment_transaction_id')) {
        $table->dropColumn('payment_transaction_id');
      }
      if (Schema::hasColumn('bookings', 'payment_status')) {
        $table->dropColumn('payment_status');
      }
      if (Schema::hasColumn('bookings', 'paid_at')) {
        $table->dropColumn('paid_at');
      }
    });
  }
};
