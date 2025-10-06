<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('room_type_id')->nullable()->after('property_id')->constrained('room_types')->nullOnDelete();
            $table->unsignedInteger('quantity')->default(1)->after('room_type_id'); // nb de chambres réservées
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('room_type_id');
            $table->dropColumn('quantity');
        });
    }
};
