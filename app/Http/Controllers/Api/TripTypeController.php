<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TripType;
use Illuminate\Http\Request;

class TripTypeController extends Controller
{
    public function index()
    {
        return response()->json(TripType::where('status', 'active')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name_en' => 'required|string|max:191',
            'name_ar' => 'nullable|string|max:191',
            'price_per_km' => 'required|numeric|min:0.01',
            'profit_margin' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
        ]);

        $tt = TripType::create($data);

        return response()->json($tt, 201);
    }

    public function show($id)
    {
        return response()->json(TripType::findOrFail($id));
    }

    public function update(Request $request, $id)
    {
        $tt = TripType::findOrFail($id);
        $data = $request->validate([
            'name_en' => 'sometimes|string|max:191',
            'name_ar' => 'nullable|string|max:191',
            'price_per_km' => 'nullable|numeric|min:0.01',
            'profit_margin' => 'nullable|numeric|min:0',
            'status' => 'nullable|string',
        ]);

        $tt->update($data);

        return response()->json($tt);
    }

    public function destroy($id)
    {
        $tt = TripType::findOrFail($id);
        $tt->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
