<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Trip;
use App\Http\Resources\TripResource;

class AdminTripController extends Controller
{
    /**
     * Return paginated trips for admin with search and filters.
     *
     * Supported query params:
     * - limit (int)
     * - search (id, client name, driver name, client phone, driver phone)
     * - status
     * - trip_type_id
     * - driver_id
     * - client_id
     * - from (YYYY-MM-DD)
     * - to (YYYY-MM-DD)
     * - sort_by (column)
     * - sort_dir (asc|desc)
     */
    public function index(Request $request)
    {
        $limit = (int) $request->input('limit', 15);
        $search = $request->input('search');
        $status = $request->input('status');
        $tripTypeId = $request->input('trip_type_id');
        $driverId = $request->input('driver_id');
        $clientId = $request->input('client_id');
        $from = $request->input('from');
        $to = $request->input('to');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = Trip::with(['client', 'driver', 'tripType']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }

                // client / driver name or phone
                $q->orWhereHas('client', function ($qc) use ($search) {
                    $qc->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });

                $q->orWhereHas('driver', function ($qd) use ($search) {
                    $qd->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($tripTypeId) {
            $query->where('trip_type_id', $tripTypeId);
        }

        if ($driverId) {
            $query->where('driver_id', $driverId);
        }

        if ($clientId) {
            $query->where('client_id', $clientId);
        }

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Prevent SQL injection in sort_by by allowing a whitelist
        $allowedSorts = ['id', 'created_at', 'started_at', 'completed_at', 'final_price', 'status'];
        if (! in_array($sortBy, $allowedSorts)) {
            $sortBy = 'id';
        }

        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDir);

        $data = $query->paginate($limit)->appends($request->query());

        return TripResource::collection($data);
    }
}
