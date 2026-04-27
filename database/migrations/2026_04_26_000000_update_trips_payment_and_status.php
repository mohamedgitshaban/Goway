<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add new columns
        Schema::table('trips', function (Blueprint $table) {
            $table->decimal('reminder', 10, 2)->default(0)->after('final_price');
            $table->boolean('is_paid')->default(false)->after('reminder');
            $table->timestamp('paid_at')->nullable()->after('is_paid');
            $table->decimal('driver_credit_amount', 10, 2)->default(0)->after('paid_at');
            $table->boolean('driver_credited')->default(false)->after('driver_credit_amount');
            $table->json('billing_breakdown')->nullable()->after('driver_credited');
        });

        // Alter enums: add 'paid' to status and 'visa' to payment_method
        // Use raw statements to ensure enum alteration works across DB drivers.
        // Adjust the SQL below if your DB is not MySQL/MariaDB.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `trips` MODIFY `status` ENUM('requested','searching_driver','driver_assigned','driver_arrived','in_progress','completed','cancelled_by_client','cancelled_by_driver','paid') NOT NULL DEFAULT 'searching_driver'");
            DB::statement("ALTER TABLE `trips` MODIFY `payment_method` ENUM('cash','wallet','visa') NOT NULL");
        } else {
            // For other DBs, attempt a more portable approach — drop and re-add column is risky if data exists.
        }
    }

    public function down(): void
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['reminder', 'is_paid', 'paid_at', 'driver_credit_amount', 'driver_credited', 'billing_breakdown']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `trips` MODIFY `status` ENUM('requested','searching_driver','driver_assigned','driver_arrived','in_progress','completed','cancelled_by_client','cancelled_by_driver') NOT NULL DEFAULT 'searching_driver'");
            DB::statement("ALTER TABLE `trips` MODIFY `payment_method` ENUM('cash','wallet') NOT NULL");
        }
    }
};
