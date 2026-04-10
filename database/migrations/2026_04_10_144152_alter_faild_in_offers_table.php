<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('title_ar')->nullable()->after('id');
            $table->string('title_en')->nullable()->after('title_ar');
            $table->text('description_ar')->nullable()->after('title_en');
            $table->text('description_en')->nullable()->after('description_ar');
        });

        // 🔥 نقل الداتا القديمة
        DB::table('offers')->update([
            'title_ar' => DB::raw('title'),
            'title_en' => DB::raw('title'),
            'description_ar' => DB::raw('description'),
            'description_en' => DB::raw('description'),
        ]);

        // 🔥 حذف الأعمدة القديمة
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['title', 'description']);
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('title')->nullable();
            $table->text('description')->nullable();
        });

        // رجّع الداتا (نختار مثلاً العربي)
        DB::table('offers')->update([
            'title' => DB::raw('title_ar'),
            'description' => DB::raw('description_ar'),
        ]);

        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['title_ar', 'title_en', 'description_ar', 'description_en']);
        });
    }
};