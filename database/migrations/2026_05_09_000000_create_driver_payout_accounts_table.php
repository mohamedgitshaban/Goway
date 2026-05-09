<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('driver_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('payment_method', ['bank_account', 'vodafone_cash']);
            // Stored as encrypted ciphertext — use text to accommodate variable length
            $table->text('account_name');
            $table->text('account_number');
            $table->text('bank_name')->nullable();
            $table->text('iban')->nullable();
            $table->timestamps();

            // One saved account per driver per payment method
            $table->unique(['user_id', 'payment_method']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('driver_payout_accounts');
    }
};
