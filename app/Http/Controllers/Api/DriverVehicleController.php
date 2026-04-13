<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Vehicle;
use App\Http\Resources\VehicleResource;

class DriverVehicleController extends Controller
{
    /**
     * List vehicles for the authenticated driver.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $limit = $request->input('limit', 10);
        $vehicles = Vehicle::with(['tripType', 'brand', 'model'])
            ->where('driver_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($limit);

        return VehicleResource::collection($vehicles);
    }

    /**
     * Store a new vehicle for the authenticated driver.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        $rules = [
            'trip_type_id' => 'required|exists:trip_types,id',
            'vehicle_brand_id' => 'required|exists:vehicle_brands,id',
            'vehicle_model_id' => 'required|exists:vehicle_models,id',
            'color' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'plate_number' => 'required|string',
            'vehicle_license_image' => 'required|mimes:jpg,jpeg,png,pdf',
            'car_front_image' => 'required|mimes:jpg,jpeg,png,pdf',
            'car_back_image' => 'required|mimes:jpg,jpeg,png,pdf',
            'car_left_image' => 'required|mimes:jpg,jpeg,png,pdf',
            'car_right_image' => 'required|mimes:jpg,jpeg,png,pdf',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // helper to store file and return public url
        $storeFile = function ($file, $folder = 'vehicles') {
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs($folder, $filename, 'public');
            // keep consistent with other parts of the app (public disk url)
            return config('filesystems.disks.public.url') . '/' . $path;
        };

        $data = $validator->validated();
        $imageFields = [
            'vehicle_license_image',
            'car_front_image',
            'car_back_image',
            'car_left_image',
            'car_right_image',
        ];

        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $storeFile($request->file($field));
            }
        }

        $data['driver_id'] = $user->id;
        $data['status'] = $data['status'] ?? 'pending';

        $vehicle = Vehicle::create($data);

        return response()->json([
            'message' => 'Vehicle created successfully',
            'vehicle' => new VehicleResource($vehicle),
        ], 201);
    }

    /**
     * Activate (make primary) a vehicle belonging to the authenticated driver.
     */
    public function activate($id)
    {
        $user = auth()->user();
        $vehicle = Vehicle::where('id', $id)->where('driver_id', $user->id)->first();
        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        // deactivate other vehicles
        Vehicle::where('driver_id', $user->id)->update(['isactive' => false]);

        // activate this one
        $vehicle->isactive = true;
        $vehicle->status = 'active';
        $vehicle->save();

        return response()->json([
            'message' => 'Vehicle activated',
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }
}
