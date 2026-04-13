<?php

namespace App\Http\Controllers\Api;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use App\Traits\HandlesMultipart;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class BaseController extends Controller
{
    use HandlesMultipart;
    protected $model;
    protected $resource;


    public function index(Request $request)
    {
        $limit  = $request->input('limit', 10);
        $search = $request->input('search');
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'desc');
        $trashed = $request->input('trashed'); // new filter
        $query = $this->model::query();
        if ($trashed === 'with') {
            $query->withTrashed();
        } elseif ($trashed === 'only') {
            $query->onlyTrashed();
        }
        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_en', 'LIKE', "%{$search}%")
                    ->orWhere('name_ar', 'LIKE', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }
        $query->orderBy($sortBy, $sortDir);
        
        // safely check if authenticated user is a driver
        $authUser = auth()->user();
        if ($authUser && method_exists($authUser, 'isDriver') && $authUser->isDriver()) {
            $query->where('status', 'active');
        }
        $data = $query->paginate($limit);

        return $this->resource::collection($data);
    }

    public function show($id)
    {
        $trip_type = $this->model::find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        return new $this->resource($trip_type);
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'name_en' => 'required|string',
            'name_ar' => 'required|string',
            'image' => 'sometimes|nullable|image',
            'price_per_km' => 'required|numeric',
            'max_distance' => 'required|numeric',
            'profit_margin' => 'required|numeric',
            'need_licence' => 'required|in:true,false,1,0',
        ]);
        if ($request->hasFile('image')) {
            $data['image'] = config('filesystems.disks.public.url') . '/' . $request->file('image')->store('trip_types', 'public');
        }

        // Normalize boolean-like input for create path as well
        if (array_key_exists('need_licence', $data)) {
            $data['need_licence'] = $this->normalizeBoolean($request->input('need_licence'));
        }


        $trip_type = $this->model::create($data);

        return response()->json([
            'message' => 'trip_type created successfully',
            'trip_type'    => new $this->resource($trip_type),
        ], 201);
    }
    public function update($id, Request $request)
    {
        $this->handleMultipart($request);
        $trip_type = $this->model::find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        $data = $request->validate([
            'name_en' => 'sometimes|required|string',
            'name_ar' => 'sometimes|required|string',
            // allow image to be included in partial updates (PATCH or POST + _method=PUT)
            'image' => 'sometimes|nullable|image',
            'max_distance' => 'sometimes|required|numeric',
            'price_per_km' => 'sometimes|required|numeric',
            'profit_margin' => 'sometimes|required|numeric',
            // validate as boolean - accepts true/false, 1/0, "true"/"false"
            'need_licence' => 'sometimes|in:true,false,1,0',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = config('filesystems.disks.public.url') . '/' . $request->file('image')->store('trip_types', 'public');
        }
        // Ensure boolean-like inputs (e.g. the string "false") are converted to actual booleans
        if (array_key_exists('need_licence', $data)) {
            $data['need_licence'] = $this->normalizeBoolean($request->input('need_licence'));
        }

        $trip_type->update($data);

        return response()->json([
            'message' => 'trip_type updated successfully',
            'trip_type'    => new $this->resource($trip_type),
        ]);
    }

    /**
     * Store uploaded image safely, delete old image if provided, and return stored asset URL.
     */
    private function normalizeBoolean($value): bool
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if (is_null($normalized)) {
            return (bool) $value;
        }
        return $normalized;
    }

    /**
     * PUT /trip_types/{id}/activate
     */
    public function activate($id)
    {
        $trip_type = $this->model::find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        $trip_type->status = 'active';
        $trip_type->save();

        return response()->json([
            'message' => 'trip_type activated successfully',
            'trip_type'    => new $this->resource($trip_type),
        ]);
    }

    /**
     * PUT /trip_types/{id}/suspend
     */
    public function suspend($id)
    {
        $trip_type = $this->model::find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        $trip_type->status = 'disactive';
        $trip_type->save();

        return response()->json([
            'message' => 'trip_type suspended successfully',
            'trip_type'    => new $this->resource($trip_type),
        ]);
    }
    public function statusToggle($id)
    {
        $trip_type = $this->model::find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        if ($trip_type->status === 'active') {
            $trip_type->status = 'disactive';
        } else {
            $trip_type->status = 'active';
        }
        $trip_type->save();

        return response()->json([
            'message' => 'trip_type status toggled successfully',
            'trip_type'    => new $this->resource($trip_type),
        ]);
    }

    public function licenceToggle($id)
    {
        $trip_type = $this->model::find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        $trip_type->need_licence = ! $trip_type->need_licence;
        $trip_type->save();

        return response()->json([
            'message' => 'trip_type licence status toggled successfully',
            'trip_type'    => new $this->resource($trip_type),
        ]);
    }
    public function destroy($id)
    {
        $trip_type = $this->model::find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        $trip_type->delete();

        return response()->json(['message' => 'trip_type soft deleted']);
    }

    public function restore($id)
    {
        $trip_type = $this->model::withTrashed()->find($id);

        if (! $trip_type) {
            return response()->json(['message' => 'trip_type not found'], 404);
        }

        $trip_type->restore();

        return response()->json(['message' => 'trip_type restored']);
    }
    public function export(Request $request)
    {
        $format = $request->input('format', 'xlsx'); // xlsx or csv

        $fileName = strtolower(class_basename($this->model)) . "_export." . $format;

        return Excel::download(new UsersExport(new $this->model), $fileName);
    }
}
