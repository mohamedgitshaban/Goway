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
            $table->string('nid_front')->nullable()->after('status');
            $table->string('nid_back')->nullable()->after('nid_front');
            $table->string('license_image')->nullable()->after('nid_back');
            $table->string('personal_image')->nullable()->after('license_image');
            $table->string('criminal_record')->nullable()->after('personal_image');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'nid_front',
                'nid_back',
                'license_image',
                'personal_image',
                'criminal_record',
            ]);
        });
    }
};
