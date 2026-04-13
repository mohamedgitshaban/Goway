<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DiscountExport;
use App\Traits\HandlesMultipart;
use Illuminate\Support\Facades\Storage;

abstract class BaseDiscountController extends Controller
{
    protected $model;
    protected $resource;
    protected $searchFields = [];
    protected $exportView = 'exports.discount';
    use HandlesMultipart;
    abstract protected function rules($id = null);

    public function index(Request $request)
    {
        // Export?
        if ($request->has('export')) {
            return $this->export($request);
        }

        $limit     = $request->input('limit', 10);
        $search    = $request->input('search');
        $tripType  = $request->input('trip_type_id');
        $status    = $request->input('is_active');
        $sortBy    = $request->input('sort_by', 'id');
        $sortDir   = $request->input('sort_dir', 'desc');

        $query = $this->model::query();

        // Filter by trip type
        if ($tripType) {
            $query->where('trip_type_id', $tripType);
        }

        // Filter by status
        if (!is_null($status)) {
            $query->where('is_active', $status);
        }

        // If authenticated user is a driver, return only driver-targeted items
        $authUser = auth()->user();
        if ($authUser->isDriver()) {
            $query->where('user_type', 'driver');
        }
        if ($authUser->isClient()) {
            $query->where('user_type', 'client');
        }

        // Search
        if ($search && count($this->searchFields)) {
            $query->where(function ($q) use ($search) {
                foreach ($this->searchFields as $field) {
                    if ($field === 'trip_type') {
                        $q->orWhereHas('tripType', function ($t) use ($search) {
                            $t->where('name_en', 'LIKE', "%{$search}%")
                                ->orWhere('name_ar', 'LIKE', "%{$search}%");
                        });
                    } else {
                        $q->orWhere($field, 'LIKE', "%{$search}%");
                    }
                }
            });
        }

        // Sorting
        if (in_array($sortBy, $this->model::getModel()->getFillable())) {
            $query->orderBy($sortBy, $sortDir);
        }

        $data = $query->paginate($limit);

        return $this->resource::collection($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $item = $this->model::create($data);

        // Handle uploaded file or base64 image
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('discounts', 'public');
            $item->image = config('filesystems.disks.public.url') . '/' . $path;
            $item->save();
        }

        return new $this->resource($item);
    }

    public function show($id)
    {
        $item = $this->model::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return new $this->resource($item);
    }

    public function update(Request $request, $id)
    {
        $this->handleMultipart($request);

        $item = $this->model::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $data = $request->validate($this->rules($id));

        $item->update($data);

        // Handle new uploaded file or base64 image; remove old file if exists
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('discounts', 'public');
            $item->image = config('filesystems.disks.public.url') . '/' . $path;
            $item->save();
        }

        return new $this->resource($item);
    }

    public function destroy($id)
    {
        $item = $this->model::find($id);

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // delete image file if present
        if (!empty($item->image) && Storage::disk('public')->exists($item->image)) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted successfully']);
    }

    protected function export(Request $request)
    {
        $items = $this->model::all();

        $format = $request->input('export', 'xlsx');
        $fileName = strtolower(class_basename($this->model)) . "_export." . $format;

        return Excel::download(
            new DiscountExport($items, $this->exportView),
            $fileName
        );
    }
}
