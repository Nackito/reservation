<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('conversations', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id'); // Client
      $table->unsignedBigInteger('owner_id'); // Propriétaire
      $table->unsignedBigInteger('booking_id')->nullable();
      $table->timestamps();

      $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
      $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
    });
  }
  public function down(): void
  {
    Schema::dropIfExists('conversations');
  }
};
