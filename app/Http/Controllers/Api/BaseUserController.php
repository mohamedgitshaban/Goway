<?php

namespace App\Http\Controllers\Api;

use App\Exports\UsersExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BaseUserController extends Controller
{
    protected $model;
    protected $resource;

    public function index(Request $request)
    {
        $limit  = $request->input('limit', 10);
        $search = $request->input('search');
        $status = $request->input('status');
        $sortBy = $request->input('sort_by', 'id');
        $sortDir = $request->input('sort_dir', 'asc');

        $query = $this->model::query();

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('first_name', 'LIKE', "%{$search}%")
                    ->orWhere('last_name', 'LIKE', "%{$search}%");
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Sorting
        if ($sortBy === 'name') {
            $query->orderByRaw("CONCAT(first_name, ' ', last_name) {$sortDir}");
        } elseif (in_array($sortBy, ['id', 'phone'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $data = $query->paginate($limit);

        return $this->resource::collection($data);
    }

    public function show($id)
    {
        $user = $this->model::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return new $this->resource($user);
    }

    /**
     * PUT /users/{id}/activate
     */
    public function activate($id)
    {
        $user = $this->model::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->status = 'active';
        $user->save();

        return response()->json([
            'message' => 'User activated successfully',
            'user'    => new $this->resource($user),
        ]);
    }

    /**
     * PUT /users/{id}/suspend
     */
    public function suspend($id)
    {
        $user = $this->model::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->status = 'disactive';
        $user->save();

        return response()->json([
            'message' => 'User suspended successfully',
            'user'    => new $this->resource($user),
        ]);
    }
    public function destroy($id)
    {
        $user = $this->model::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->delete();

        return response()->json(['message' => 'User soft deleted']);
    }

    public function restore($id)
    {
        $user = $this->model::withTrashed()->find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->restore();

        return response()->json(['message' => 'User restored']);
    }
    public function export(Request $request)
    {
        $format = $request->input('format', 'xlsx'); // xlsx or csv

        $fileName = strtolower(class_basename($this->model)) . "_export." . $format;

        return Excel::download(new UsersExport(new $this->model), $fileName);
    }
}
