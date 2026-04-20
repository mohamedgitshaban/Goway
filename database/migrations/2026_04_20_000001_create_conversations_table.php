<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // support = general support chat
            // trip_support = support chat linked to a trip
            // trip_chat = client <-> driver chat inside a trip
            $table->enum('type', ['support', 'trip_support', 'trip_chat']);

            $table->foreignId('trip_id')->nullable()->constrained('trips')->nullOnDelete();

            // The user who initiated the conversation (client or driver)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // The admin assigned to support conversations (nullable for trip_chat)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('status', ['open', 'closed'])->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['admin_id', 'status']);
            $table->index(['trip_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
