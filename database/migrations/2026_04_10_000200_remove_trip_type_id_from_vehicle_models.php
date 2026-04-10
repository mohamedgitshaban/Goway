<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveTripTypeIdFromVehicleModels extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('vehicle_models')) {
            return;
        }

        // Only attempt to drop if column exists
        if (Schema::hasColumn('vehicle_models', 'trip_type_id')) {
            Schema::table('vehicle_models', function (Blueprint $table) {
                // try to drop foreign key safely
                try {
                    $table->dropForeign(['trip_type_id']);
                } catch (\Throwable $e) {
                    // ignore if FK does not exist or different name
                }

                // finally drop the column
                if (Schema::hasColumn('vehicle_models', 'trip_type_id')) {
                    $table->dropColumn('trip_type_id');
                }
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('vehicle_models')) {
            return;
        }

        // re-add the trip_type_id column and FK if it does not exist
        if (! Schema::hasColumn('vehicle_models', 'trip_type_id')) {
            Schema::table('vehicle_models', function (Blueprint $table) {
                $table->unsignedBigInteger('trip_type_id')->nullable()->after('id');
                $table->foreign('trip_type_id')->references('id')->on('trip_types')->onDelete('set null');
            });
        }
    }
}
