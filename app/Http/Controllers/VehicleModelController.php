<?php

namespace App\Http\Controllers;

use App\Models\TripType;

class VehicleModelController extends Controller
{
    public function vehicleOptions($id)
    {
        $tripType = TripType::with('vehicleModels')->findOrFail($id);

        return response()->json([
            'trip_type' => $tripType->only(['id', 'name_en', 'name_ar', 'need_licence']),
            'models' => $tripType->vehicleModels->map(function ($m) {
                return [
                    'id' => $m->id,
                    'name' => $m->name,
                    'min_year' => $m->min_year,
                    'max_year' => $m->max_year,
                    'years' => range($m->min_year, $m->max_year),
                ];
            }),
        ]);
    }
}
