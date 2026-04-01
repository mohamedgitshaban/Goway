<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('trips', function (Blueprint $table) {

            // سعر التفاوض (اختياري)
            $table->decimal('negotiation_price', 10, 2)
                  ->nullable()
                  ->after('final_price');

            // حالة التفاوض
            $table->enum('negotiation_status', [
                'none',       // لا يوجد تفاوض
                'pending',    // السائق أرسل عرض
                'counter',    // العميل أرسل Counter Offer
                'accepted',   // العميل قبل العرض
                'rejected'    // العميل رفض العرض
            ])->default('none')->after('negotiation_price');
        });
    }

    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->dropColumn(['negotiation_price', 'negotiation_status']);
        });
    }
};
