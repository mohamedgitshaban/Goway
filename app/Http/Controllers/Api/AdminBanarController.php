<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BanarResource;
use App\Models\Banar;
use App\Traits\HandlesMultipart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminBanarController extends Controller
{
    use HandlesMultipart;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $limit = (int) $request->input('limit', 15);
        $search = $request->input('search');
        $isActive = $request->input('is_active');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = Banar::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }
                $q->orWhere('title_ar', 'like', "%{$search}%")
                    ->orWhere('title_en', 'like', "%{$search}%");
            });
        }

        if ($isActive !== null && $isActive !== '') {
            $status = $isActive == 1 ? 'active' : 'inactive';
            $query->where('status', $status);
        }

        $query->orderBy($sortBy, $sortDir);

        $banars = $query->paginate($limit);

        return BanarResource::collection($banars);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title_ar' => 'required|string|max:255',
            'title_en' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'usertype' => 'required|in:client,driver',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = config('filesystems.disks.public.url') . '/' . $request->file('image')->store('banars', 'public');
        }

        $banar = Banar::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Banar created successfully',
            'data' => new BanarResource($banar)
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Banar $banar)
    {
        return response()->json([
            'status' => true,
            'data' => new BanarResource($banar)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Banar $banar)
    {
        $this->handleMultipart($request);

        $data = $request->validate([
            'title_ar' => 'sometimes|required|string|max:255',
            'title_en' => 'sometimes|required|string|max:255',
            'image' => $request->hasFile('image') ? 'image|mimes:jpeg,png,jpg,gif|max:2048' : 'nullable',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after_or_equal:start_date',
            'usertype' => 'sometimes|required|in:client,driver',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            if ($banar->image) {
                // Determine raw path from full URL
                $baseUrl = config('filesystems.disks.public.url') . '/';
                $oldPath = str_replace($baseUrl, '', $banar->image);
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }
            $data['image'] = config('filesystems.disks.public.url') . '/' . $request->file('image')->store('banars', 'public');
        } else {
            // Remove 'image' from the update payload if no new file is uploaded
            // so we don't accidentally update the database column with a raw string (the old URL)
            unset($data['image']);
        }

        $banar->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Banar updated successfully',
            'data' => new BanarResource($banar)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Banar $banar)
    {
        if ($banar->image) {
            $baseUrl = config('filesystems.disks.public.url') . '/';
            $oldPath = str_replace($baseUrl, '', $banar->image);
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $banar->delete();

        return response()->json([
            'status' => true,
            'message' => 'Banar deleted successfully'
        ]);
    }
}
