<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('trips_cancelled_count')->default(0)->after('status');
            $table->unsignedInteger('trips_completed_count')->default(0)->after('trips_cancelled_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['trips_cancelled_count', 'trips_completed_count']);
        });
    }
};
