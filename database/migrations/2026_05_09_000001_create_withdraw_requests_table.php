<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // the driver
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'approved', 'rejected', 'processing', 'paid', 'failed'])
                  ->default('pending');
            $table->enum('payment_method', ['bank_account', 'vodafone_cash']);
            // Snapshot of payout account at time of request (encrypted, text for ciphertext length)
            $table->text('account_name');
            $table->text('account_number');
            $table->text('bank_name')->nullable();
            $table->text('iban')->nullable();
            // Optional reference to the saved payout account used
            $table->foreignId('driver_payout_account_id')
                  ->nullable()
                  ->constrained('driver_payout_accounts')
                  ->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('payout_reference')->nullable(); // reference returned by payment gateway
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
    }
};
