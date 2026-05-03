<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `trips` MODIFY `status` ENUM('requested','searching_driver','driver_assigned','driver_arrived','in_progress','completed','cancelled_by_client','cancelled_by_driver','paid','cancelled_by_system') NOT NULL DEFAULT 'searching_driver'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `trips` MODIFY `status` ENUM('requested','searching_driver','driver_assigned','driver_arrived','in_progress','completed','cancelled_by_client','cancelled_by_driver','paid') NOT NULL DEFAULT 'searching_driver'");
        }
    }
};
