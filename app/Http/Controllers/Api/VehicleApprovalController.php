<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleApprovalController extends Controller
{
    /**
     * List vehicles awaiting approval.
     *
     * - Optional query param "status" accepts single value or comma-separated values.
     *   Example: ?status=pending or ?status=pending,rejected
     * - Defaults to common awaiting statuses: pending,inreview,waiting
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 10);

        if ($request->filled('status')) {
            $statuses = array_map('trim', explode(',', $request->input('status')));
            $query = Vehicle::with(['driver', 'tripType', 'brand', 'model'])->whereIn('status', $statuses);
        } else {
            // default statuses to show as "awaiting approval"
            $query = Vehicle::with(['driver', 'tripType', 'brand', 'model'])
                ->whereIn('status', ['pending']);
        }

        $query->orderBy('created_at', 'desc');

        $data = $query->paginate($limit);

        return VehicleResource::collection($data);
    }

    /**
     * Accept vehicle (admin).
     */
    public function accept($id)
    {
        $vehicle = Vehicle::find($id);

        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        $vehicle->status = 'approved';
        $vehicle->isactive = false;
        $vehicle->rejection_reason = null;
        $vehicle->save();

        // Optionally activate the driver if relation exists
        if ($vehicle->driver) {
            $vehicle->driver->status = 'active';
            $vehicle->driver->save();
        }

        return response()->json([
            'message' => 'Vehicle accepted successfully',
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }

    /**
     * Reject vehicle with required reason (admin).
     */
    public function reject(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $vehicle = Vehicle::find($id);

        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        $vehicle->status = 'rejected';
        $vehicle->isactive = false;
        $vehicle->rejection_reason = $request->input('reason');
        $vehicle->save();

        return response()->json([
            'message' => 'Vehicle rejected',
            'vehicle' => new VehicleResource($vehicle),
        ]);
    }

    /**
     * Show single vehicle details (admin).
     */
    public function show($id)
    {
        $vehicle = Vehicle::with(['driver', 'tripType', 'brand', 'model'])->find($id);

        if (! $vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        return new VehicleResource($vehicle);
    }
}
