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
        $brands = require database_path('data/brands.php');
        
        foreach ($brands as $brandCode) {
            $brandName = ucwords(str_replace('_', ' ', $brandCode));
            $modelsInfo = require database_path("data/models/{$brandCode}.php");

            foreach ($modelsInfo as $item) {
                // Determine the correct trip type directly from the file configuration!
                $tripTypeId = $item['type_id'];
                
                // Ensure the trip exists to avoid FK error
                $tripExists = \App\Models\TripType::find($tripTypeId);
                $createData = [];
                if ($tripExists) {
                    $createData['trip_type_id'] = $tripTypeId;
                }

                $brand = VehicleBrand::firstOrCreate(
                    ['name' => $brandName],
                    $createData
                );

                if ($tripExists && is_null($brand->trip_type_id)) {
                    $brand->trip_type_id = $tripTypeId;
                    $brand->save();
                }
                
                VehicleModel::updateOrCreate(
                    ['name' => $brandName . ' ' . $item['name'], 'min_year' => $item['min_year'], 'max_year' => $item['max_year']],
                    [
                        'vehicle_brand_id' => $brand->id,
                        'trip_type_id'     => $brand->trip_type_id, // use brand's trip type to ensure consistency
                        'min_year'         => $item['min_year'],
                        'max_year'         => $item['max_year'],
                    ]
                );
            }
        }
    }

}
