<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('payments', function (Blueprint $table) {
      $table->id();
      $table->foreignId('booking_id')->nullable()->constrained('bookings')->nullOnDelete();
      $table->string('transaction_id')->index();
      $table->string('status')->nullable();
      $table->string('source')->nullable(); // notify | return
      $table->boolean('signature_valid')->nullable();
      $table->json('payload')->nullable();
      $table->json('headers')->nullable();
      $table->string('ip')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('payments');
  }
};
