<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      if (!Schema::hasColumn('users', 'theme')) {
        $table->string('theme')->default('system')->after('password');
      }
      if (!Schema::hasColumn('users', 'currency')) {
        $table->string('currency', 8)->default('XOF')->after('theme');
      }
      if (!Schema::hasColumn('users', 'locale')) {
        $table->string('locale', 8)->default('fr')->after('currency');
      }
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      if (Schema::hasColumn('users', 'locale')) {
        $table->dropColumn('locale');
      }
      if (Schema::hasColumn('users', 'currency')) {
        $table->dropColumn('currency');
      }
      if (Schema::hasColumn('users', 'theme')) {
        $table->dropColumn('theme');
      }
    });
  }
};
