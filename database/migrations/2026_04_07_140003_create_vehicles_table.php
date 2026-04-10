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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('trip_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vehicle_model_id')->constrained()->cascadeOnDelete();

            $table->string('color'); // no table needed
            $table->unsignedSmallInteger('year');
            $table->string('plate_number')->nullable();
            $table->string('vehicle_license_image')->nullable();
            $table->string('car_front_image')->nullable();
            $table->string('car_back_image')->nullable();
            $table->string('car_left_image')->nullable();
            $table->string('car_right_image')->nullable();
            $table->boolean('isactive')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('rejection_reason')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
