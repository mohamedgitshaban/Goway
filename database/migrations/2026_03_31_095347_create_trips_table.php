<?php
// database/migrations/2026_03_31_000000_create_trips_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();

            $table->foreignId('client_id')->constrained('users');
            $table->foreignId('driver_id')->nullable()->constrained('users');
            $table->foreignId('trip_type_id')->constrained('trip_types');

            $table->enum('status', [
                'requested',
                'searching_driver',
                'driver_assigned',
                'driver_arrived',
                'in_progress',
                'completed',
                'cancelled_by_client',
                'cancelled_by_driver',
            ])->default('searching_driver');

            $table->enum('payment_method', ['cash', 'wallet']);

            // pricing
            $table->decimal('distance_km', 8, 2)->nullable();
            $table->decimal('base_fare', 10, 2)->nullable();
            $table->decimal('price_per_km', 10, 2)->nullable();
            $table->decimal('original_price', 10, 2)->nullable();
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('final_price', 10, 2)->nullable();

            $table->foreignId('offer_id')->nullable()->constrained('offers');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons');

            // negotiation
            $table->boolean('negotiation_enabled')->default(false);
            $table->decimal('negotiated_price_before', 10, 2)->nullable();
            $table->decimal('negotiated_price_after', 10, 2)->nullable();

            // locations (basic – waypoints في جدول منفصل)
            $table->decimal('origin_lat', 10, 7);
            $table->decimal('origin_lng', 10, 7);
            $table->string('origin_address')->nullable();

            $table->decimal('destination_lat', 10, 7);
            $table->decimal('destination_lng', 10, 7);
            $table->string('destination_address')->nullable();

            // timestamps for lifecycle
            $table->timestamp('driver_assigned_at')->nullable();
            $table->timestamp('driver_arrived_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancelled_by')->nullable(); // client / driver
            $table->string('cancel_reason')->nullable();
            $table->text('cancel_description')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
