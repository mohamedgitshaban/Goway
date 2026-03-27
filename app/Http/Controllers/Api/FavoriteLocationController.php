<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FavoriteLocation;
use Illuminate\Http\Request;

class FavoriteLocationController extends Controller
{
    // 🔹 INDEX
    public function index()
    {
        $locations = auth()->user()
            ->favoriteLocations()
            ->select('id', 'title', 'lat', 'long')
            ->get();

        return response()->json([
            'data' => $locations
        ]);
    }

    // 🔹 STORE
    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'lat'   => 'required|numeric',
            'long'  => 'required|numeric',
        ]);

        $location = auth()->user()->favoriteLocations()->create($data);

        return response()->json([
            'message' => 'Created successfully',
            'data' => $location
        ], 201);
    }

    // 🔹 SHOW (by id + custom 404)
    public function show($id)
    {
        $location = auth()->user()
            ->favoriteLocations()
            ->select('id', 'title', 'lat', 'long')
            ->find($id);

        if (!$location) {
            return response()->json([
                'message' => 'Location not found'
            ], 404);
        }

        return response()->json([
            'data' => $location
        ]);
    }

    // 🔹 UPDATE (by id + custom 404)
    public function update(Request $request, $id)
    {
        $location = auth()->user()
            ->favoriteLocations()
            ->find($id);

        if (!$location) {
            return response()->json([
                'message' => 'Location not found'
            ], 404);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'lat'   => 'sometimes|numeric',
            'long'  => 'sometimes|numeric',
        ]);

        $location->update($data);

        return response()->json([
            'message' => 'Updated successfully',
            'data' => $location->only(['id', 'title', 'lat', 'long'])
        ]);
    }

    // 🔹 DELETE (by id + custom 404)
    public function destroy($id)
    {
        $location = auth()->user()
            ->favoriteLocations()
            ->find($id);

        if (!$location) {
            return response()->json([
                'message' => 'Location not found'
            ], 404);
        }

        $location->delete();

        return response()->json([
            'message' => 'Deleted successfully'
        ]);
    }
}