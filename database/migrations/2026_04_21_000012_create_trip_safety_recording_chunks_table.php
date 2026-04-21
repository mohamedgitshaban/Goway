<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trip_safety_recording_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trip_safety_recording_id')->constrained('trip_safety_recordings')->cascadeOnDelete();
            $table->unsignedInteger('chunk_index');
            $table->unsignedInteger('recorded_second')->nullable();
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->unique(['trip_safety_recording_id', 'chunk_index'], 'trip_recording_chunk_unique');
            $table->index(['trip_safety_recording_id', 'recorded_second'], 'trip_recording_second_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trip_safety_recording_chunks');
    }
};
