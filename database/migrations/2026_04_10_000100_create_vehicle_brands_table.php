<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVehicleBrandsTable extends Migration
{
    public function up()
    {
        Schema::create('vehicle_brands', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trip_type_id')->nullable()->index();
            $table->string('name')->unique();
            $table->timestamps();
            $table->foreign('trip_type_id')->references('id')->on('trip_types')->onDelete('set null');
        });

        // add vehicle_brand_id to vehicle_models if not exists
        if (Schema::hasTable('vehicle_models')) {
            Schema::table('vehicle_models', function (Blueprint $table) {
                if (! Schema::hasColumn('vehicle_models', 'vehicle_brand_id')) {
                    $table->unsignedBigInteger('vehicle_brand_id')->nullable()->after('trip_type_id');
                    $table->foreign('vehicle_brand_id')->references('id')->on('vehicle_brands')->onDelete('set null');
                }
            });
        }

        // add vehicle_brand_id to vehicles if not exists
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (! Schema::hasColumn('vehicles', 'vehicle_brand_id')) {
                    $table->unsignedBigInteger('vehicle_brand_id')->nullable()->after('trip_type_id');
                    $table->foreign('vehicle_brand_id')->references('id')->on('vehicle_brands')->onDelete('set null');
                }
            });
        }
    }

    public function down()
    {
        // drop FK and column from vehicles
        if (Schema::hasTable('vehicles')) {
            Schema::table('vehicles', function (Blueprint $table) {
                if (Schema::hasColumn('vehicles', 'vehicle_brand_id')) {
                    $table->dropForeign(['vehicle_brand_id']);
                    $table->dropColumn('vehicle_brand_id');
                }
            });
        }

        // drop FK and column from vehicle_models
        if (Schema::hasTable('vehicle_models')) {
            Schema::table('vehicle_models', function (Blueprint $table) {
                if (Schema::hasColumn('vehicle_models', 'vehicle_brand_id')) {
                    $table->dropForeign(['vehicle_brand_id']);
                    $table->dropColumn('vehicle_brand_id');
                }
            });
        }

        Schema::dropIfExists('vehicle_brands');
    }
}
