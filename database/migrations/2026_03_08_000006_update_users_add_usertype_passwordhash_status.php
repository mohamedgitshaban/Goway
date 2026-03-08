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
        Schema::table('users', function (Blueprint $table) {
            // usertype to indicate super_admin|admin|driver|client
            $table->string('usertype')->default('client')->after('email')->index();

            // optional separate password hash column (kept for compatibility)
            $table->string('password_hash')->nullable()->after('password');

            // status (active, suspended, deleted, etc.)
            $table->string('status')->default('active')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['usertype', 'password_hash', 'status']);
        });
    }
};
