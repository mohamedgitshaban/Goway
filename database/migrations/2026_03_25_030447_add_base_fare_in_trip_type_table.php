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
        Schema::table('trip_types', function (Blueprint $table) {
            $table->string('code')->unique()->nullable(); // CAR, BIKE, BUS
            $table->decimal('base_fare', 10, 2)->nullable();         // فتح العداد
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_types', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->dropColumn('base_fare');
        });
    }
};
