<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('bookings', function (Blueprint $table) {
      $table->timestamp('review_reminder_sent_at')->nullable()->after('paid_at');
      $table->timestamp('review_reminder_sent_7d_at')->nullable()->after('review_reminder_sent_at');
      $table->index(['review_reminder_sent_at']);
      $table->index(['review_reminder_sent_7d_at']);
    });
  }

  public function down(): void
  {
    Schema::table('bookings', function (Blueprint $table) {
      $table->dropIndex(['review_reminder_sent_at']);
      $table->dropIndex(['review_reminder_sent_7d_at']);
      $table->dropColumn(['review_reminder_sent_at', 'review_reminder_sent_7d_at']);
    });
  }
};
