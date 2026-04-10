<?php

namespace Database\Seeders;

use App\Models\Trip;
use App\Models\TripType;
use App\Models\VehicleModel;
use App\Models\VehicleBrand;
use Illuminate\Database\Seeder;

class CarsModelsSeeder extends Seeder
{
    public function run()
    {
        $models = [
            // 1 => Economy / Uber X
            1 => [
                ['name' => 'Toyota Corolla', 'min_year' => 2015, 'max_year' => 2025],
                ['name' => 'Hyundai Elantra', 'min_year' => 2014, 'max_year' => 2025],
                ['name' => 'Kia Cerato', 'min_year' => 2015, 'max_year' => 2025],
                ['name' => 'Chevrolet Optra', 'min_year' => 2012, 'max_year' => 2023],
                ['name' => 'Renault Logan', 'min_year' => 2013, 'max_year' => 2024],
                ['name' => 'Nissan Sentra', 'min_year' => 2016, 'max_year' => 2025],
                ['name' => 'Peugeot 301', 'min_year' => 2014, 'max_year' => 2024],
                ['name' => 'Skoda Octavia', 'min_year' => 2015, 'max_year' => 2025],
                ['name' => 'MG 5', 'min_year' => 2020, 'max_year' => 2025],
                ['name' => 'Chery Arrizo 5', 'min_year' => 2018, 'max_year' => 2025],
                ['name' => 'Fiat Tipo', 'min_year' => 2017, 'max_year' => 2025],
                ['name' => 'Toyota Yaris', 'min_year' => 2016, 'max_year' => 2025],
                ['name' => 'Hyundai Accent', 'min_year' => 2015, 'max_year' => 2025],
            ],

            // 2 => Budget / Old Cars
            2 => [
                ['name' => 'BYD F3', 'min_year' => 2010, 'max_year' => 2023],
                ['name' => 'Nissan Sunny', 'min_year' => 2012, 'max_year' => 2024],
                ['name' => 'Lada Granta', 'min_year' => 2015, 'max_year' => 2024],
                ['name' => 'Chevrolet Aveo', 'min_year' => 2011, 'max_year' => 2022],
                ['name' => 'Hyundai Verna', 'min_year' => 2010, 'max_year' => 2020],
                ['name' => 'Daewoo Lanos', 'min_year' => 2008, 'max_year' => 2018],
                ['name' => 'Geely Emgrand', 'min_year' => 2013, 'max_year' => 2023],
                ['name' => 'Chery Tiggo 3', 'min_year' => 2015, 'max_year' => 2024],
                ['name' => 'Suzuki Ciaz', 'min_year' => 2015, 'max_year' => 2023],
                ['name' => 'Proton Saga', 'min_year' => 2012, 'max_year' => 2022],
                ['name' => 'Fiat Siena', 'min_year' => 2009, 'max_year' => 2019],
                ['name' => 'Opel Astra', 'min_year' => 2010, 'max_year' => 2020],
            ],

            // 3 => Motorcycles
            3 => [
                ['name' => 'Honda CG 150', 'min_year' => 2010, 'max_year' => 2025],
                ['name' => 'Bajaj Boxer 150', 'min_year' => 2012, 'max_year' => 2025],
                ['name' => 'TVS Apache RTR 160', 'min_year' => 2015, 'max_year' => 2025],
                ['name' => 'Yamaha FZ 150', 'min_year' => 2014, 'max_year' => 2025],
                ['name' => 'Suzuki GD 110', 'min_year' => 2013, 'max_year' => 2024],
                ['name' => 'Honda CBR 150R', 'min_year' => 2016, 'max_year' => 2025],
                ['name' => 'Kawasaki Ninja 250', 'min_year' => 2015, 'max_year' => 2025],
                ['name' => 'Benelli TNT 150', 'min_year' => 2017, 'max_year' => 2025],
                ['name' => 'SYM Wolf 150', 'min_year' => 2014, 'max_year' => 2023],
                ['name' => 'Dayun DY150', 'min_year' => 2012, 'max_year' => 2022],
            ],

            // 4 => TukTuk / 3 Wheels
            4 => [
                ['name' => 'Bajaj RE', 'min_year' => 2015, 'max_year' => 2025],
                ['name' => 'TVS King', 'min_year' => 2016, 'max_year' => 2025],
                ['name' => 'Piaggio Ape', 'min_year' => 2014, 'max_year' => 2024],
                ['name' => 'Bajaj Maxima', 'min_year' => 2015, 'max_year' => 2025],
                ['name' => 'Atul Auto Rickshaw', 'min_year' => 2013, 'max_year' => 2023],
            ],
        ];

        foreach ($models as $tripTypeId => $items) {
            // keep cache of created brands per trip type by brand name
            $brandCache = [];
            foreach ($items as $item) {
                // derive brand from model name (first word)
                $parts = preg_split('/\s+/', trim($item['name']));
                $brandName = $parts[0] ?? $item['name'];

                if (! isset($brandCache[$brandName])) {
                    // Only set trip_type_id if the TripType exists to avoid FK violations
                    $tripExists = \App\Models\TripType::find($tripTypeId);
                    $createData = [];
                    if ($tripExists) {
                        $createData['trip_type_id'] = $tripTypeId;
                    }

                    // find or create brand by name; if TripType exists we set trip_type_id when creating
                    $brand = VehicleBrand::firstOrCreate(
                        ['name' => $brandName],
                        $createData
                    );

                    // If brand exists but currently has no trip_type_id and trip exists, set it
                    if ($tripExists && is_null($brand->trip_type_id)) {
                        $brand->trip_type_id = $tripTypeId;
                        $brand->save();
                    }

                    $brandCache[$brandName] = $brand->id;
                }

                VehicleModel::updateOrCreate(
                    ['name' => $item['name']],
                    [
                        'vehicle_brand_id' => $brandCache[$brandName],
                        'min_year'    => $item['min_year'],
                        'max_year'    => $item['max_year'],
                    ]
                );
            }
        }
    }

}
