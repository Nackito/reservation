<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('search_states', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('user_id')->nullable()->index();
      $table->string('session_id', 100)->nullable()->index();
      $table->unsignedBigInteger('property_id')->nullable()->index();
      $table->string('date_range', 100)->nullable();
      $table->date('check_in')->nullable();
      $table->date('check_out')->nullable();
      $table->timestamps();

      // Index composite utile pour upsert logique
      $table->index(['user_id', 'session_id', 'property_id'], 'search_states_identity_idx');
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('search_states');
  }
};
