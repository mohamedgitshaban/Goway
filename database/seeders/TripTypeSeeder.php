<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TripType;
use Illuminate\Support\Facades\DB;

class TripTypeSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('trip_types')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        // علشان نبدأ من id = 1
        $tripTypes = [
            [
                'id' => 1,
                'name_en' => 'comfort',
                'name_ar' => 'مريحة',
                'base_fare' => 10,
                'code' => 'ECO',
                'image' => 'economy.png',
                'price_per_km' => 2.5,
                'max_distance' => 50,
                'profit_margin' => 10,
                'status' => 1,
                'need_licence' => false,
            ],
            [
                'id' => 2,
                'name_en' => 'Economy',
                'name_ar' => 'اقتصادية',
                'base_fare' => 15,
                'code' => 'STD',
                'image' => 'standard.png',
                'price_per_km' => 3,
                'max_distance' => 70,
                'profit_margin' => 12,
                'status' => 1,
                'need_licence' => false,
            ],
            [
                'id' => 3,
                'name_en' => 'Motorcycles',
                'name_ar' => 'دراجات نارية',
                'base_fare' => 25,
                'code' => 'PRM',
                'image' => 'premium.png',
                'price_per_km' => 4.5,
                'max_distance' => 100,
                'profit_margin' => 15,
                'status' => 1,
                'need_licence' => true,
            ],
            [
                'id' => 4,
                'name_en' => 'TokTok',
                'name_ar' => 'توك توك',
                'base_fare' => 40,
                'code' => 'VIP',
                'image' => 'vip.png',
                'price_per_km' => 6,
                'max_distance' => 150,
                'profit_margin' => 20,
                'status' => 1,
                'need_licence' => true,
            ],
        ];

        foreach ($tripTypes as $type) {
            TripType::create($type);
        }
    }
}
