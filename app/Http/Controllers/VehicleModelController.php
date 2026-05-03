<?php

namespace App\Http\Controllers;

use App\Models\TripType;
use App\Models\VehicleBrand;
use Illuminate\Http\Request;

class VehicleModelController extends Controller
{
    // return all vehicle brands (optionally filter by trip_type_id via ?trip_type_id=)
    public function brands($tripTypeId)
    {

        $brands = VehicleBrand::where('trip_type_id', $tripTypeId)->orderBy('name')->get(['id', 'trip_type_id', 'name']);
        return response()->json(['brands' => $brands]);
    }

    // return all models for a brand
    public function brandModels(Request $request)
    {
        $brandId = $request->query('brand_id');
        if (! $brandId) {
            return response()->json(['message' => 'brand_id query parameter is required'], 400);
        }
        $models = \App\Models\VehicleModel::where('vehicle_brand_id', $brandId)
            ->orderBy('name');
        if ($request->has('trip_type_id')) {
            $models->where('trip_type_id', $request->query('trip_type_id'));
        }
        return response()->json([
            'brand_id' => (int) $brandId,
            'models' => $models->get(['id', 'vehicle_brand_id', 'name', 'min_year', 'max_year']),
        ]);
    }

    public function vehicleOptions($id)
    {
        // load brands and their models for this trip type
        $tripType = TripType::with(['vehicleBrands.vehicleModels'])->findOrFail($id);

        $brands = $tripType->vehicleBrands->map(function ($brand) {
            return [
                'id' => $brand->id,
                'name' => $brand->name,
                'models' => $brand->vehicleModels->map(function ($m) {
                    return [
                        'id' => $m->id,
                        'name' => $m->name,
                        'min_year' => $m->min_year,
                        'max_year' => $m->max_year,
                        'years' => range($m->min_year, $m->max_year),
                    ];
                })->values(),
            ];
        })->values();

        return response()->json([
            'trip_type' => $tripType->only(['id', 'name_en', 'name_ar', 'need_licence']),
            'brands' => $brands,
        ]);
    }
}
