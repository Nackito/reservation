<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('payment_methods', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->cascadeOnDelete();
      $table->string('brand');
      $table->string('last4', 4);
      $table->unsignedTinyInteger('exp_month');
      $table->unsignedSmallInteger('exp_year');
      $table->string('token')->nullable();
      $table->boolean('is_default')->default(false);
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('payment_methods');
  }
};
