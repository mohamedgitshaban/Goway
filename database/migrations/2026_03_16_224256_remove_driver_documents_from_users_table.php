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
           $table->dropColumn([
            'nid_front',
            'nid_back',
            'license_image',
            'criminal_record',
        ]);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
        $table->string('nid_front')->nullable();
        $table->string('nid_back')->nullable();
        $table->string('license_image')->nullable();
        $table->string('criminal_record')->nullable();
    });
    }
};
