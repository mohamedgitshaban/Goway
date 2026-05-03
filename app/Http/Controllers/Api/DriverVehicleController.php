<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\HandlesMultipart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Models\Vehicle;
use App\Http\Resources\VehicleResource;
use App\Models\TripType;

class DriverVehicleController extends Controller
{
    use HandlesMultipart;

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

        $needLicence = false;
        if ($request->filled('trip_type_id')) {
            $tripType = TripType::find($request->input('trip_type_id'));
            $needLicence = $tripType && $tripType->need_licence;
        }

        $rules = [
            'trip_type_id' => 'required|exists:trip_types,id',
            'vehicle_brand_id' => 'required|exists:vehicle_brands,id',
            'vehicle_model_id' => 'required|exists:vehicle_models,id',
            'color' => 'required|string',
            'year' => 'required|integer|min:1900|max:' . date('Y'),
            'plate_number' => 'required|string|unique:vehicles,plate_number',
            'vehicle_license_image' => ($needLicence ? 'required' : 'nullable') . '|mimes:jpg,jpeg,png,pdf',
            'car_front_image' => 'required|mimes:jpg,jpeg,png,pdf',
            'car_back_image' => 'required|mimes:jpg,jpeg,png,pdf',
            'car_left_image' => 'required|mimes:jpg,jpeg,png,pdf',
            'car_right_image' => 'required|mimes:jpg,jpeg,png,pdf',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

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
                $data[$field] = config('filesystems.disks.public.url') . '/' .$this->storeVehicleFile($request->file($field));
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

    public function update(Request $request, $id)
    {
        $this->handleMultipart($request);
        $user = auth()->user();
        $vehicle = Vehicle::where('id', $id)->where('driver_id', $user->id)->first();

        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        $rules = [
            'trip_type_id' => 'sometimes|exists:trip_types,id',
            'vehicle_brand_id' => 'sometimes|exists:vehicle_brands,id',
            'vehicle_model_id' => 'sometimes|exists:vehicle_models,id',
            'color' => 'sometimes|string',
            'year' => 'sometimes|integer|min:1900|max:' . date('Y'),
            'plate_number' => 'sometimes|string|unique:vehicles,plate_number,' . $vehicle->id,
            'vehicle_license_image' => $this->fileOrPathRule($request, 'vehicle_license_image'),
            'car_front_image' => $this->fileOrPathRule($request, 'car_front_image'),
            'car_back_image' => $this->fileOrPathRule($request, 'car_back_image'),
            'car_left_image' => $this->fileOrPathRule($request, 'car_left_image'),
            'car_right_image' => $this->fileOrPathRule($request, 'car_right_image'),
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $imageFields = [
            'vehicle_license_image',
            'car_front_image',
            'car_back_image',
            'car_left_image',
            'car_right_image',
        ];

        foreach ($imageFields as $field) {
            if (array_key_exists($field, $data) && ! $request->hasFile($field)) {
                $data[$field] = $data[$field]
                    ? $this->normalizeStoredFilePath($data[$field])
                    : null;
            }

            if ($request->hasFile($field) && $request->file($field)->isValid()) {
                if ($vehicle->$field) {
                    $this->deleteStoredFile($vehicle->$field);
                }

                $data[$field] = config('filesystems.disks.public.url') . '/' .$this->storeVehicleFile($request->file($field));
            }
        }

        $vehicle->fill($data);
        $vehicle->save();
        $vehicle->load(['tripType', 'brand', 'model']);

        return response()->json([
            'message' => 'Vehicle updated successfully',
            'vehicle' => new VehicleResource($vehicle),
        ]);
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
        if ($vehicle->status !== Vehicle::STATUS_APPROVED) {
            return response()->json(['message' => 'Only approved vehicles can be activated'], 400);
        }
        // deactivate other vehicles
        Vehicle::where('driver_id', $user->id)->update(['isactive' => false]);

        // activate this one
        $vehicle->isactive = true;
        $vehicle->save();

        return response()->json([
            'message' => 'Vehicle activated',
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }

    private function fileOrPathRule(Request $request, string $field): string
    {
        return $request->hasFile($field)
            ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120'
            : 'nullable|string|max:2048';
    }

    private function storeVehicleFile($file, string $folder = 'vehicles'): string
    {
        $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

        return $file->storeAs($folder, $filename, 'public');
    }

    private function deleteStoredFile(?string $urlOrPath): void
    {
        $relativePath = $this->normalizeStoredFilePath($urlOrPath);

        if ($relativePath && Storage::disk('public')->exists($relativePath)) {
            Storage::disk('public')->delete($relativePath);
        }
    }

    private function normalizeStoredFilePath(?string $urlOrPath): ?string
    {
        if (! $urlOrPath) {
            return null;
        }

        $storageSegment = '/storage/';
        if (strpos($urlOrPath, $storageSegment) !== false) {
            return substr($urlOrPath, strpos($urlOrPath, $storageSegment) + strlen($storageSegment));
        }

        return ltrim($urlOrPath, '/');
    }
}
