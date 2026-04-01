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
        Schema::create('driver_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');

            $table->integer('age');

            // Driver NID
            $table->string('nid_front')->nullable();
            $table->string('nid_back')->nullable();

            // Parent NID (required if age < 18)
            $table->string('parent_nid_front')->nullable();
            $table->string('parent_nid_back')->nullable();

            // License (nullable)
            $table->string('license_image')->nullable();

            // Criminal record (required)
            $table->string('criminal_record');

            // Status
            $table->enum('status', ['inreview', 'accepted', 'rejected'])->default('inreview');
            $table->text('reject_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_documents');
    }
};
