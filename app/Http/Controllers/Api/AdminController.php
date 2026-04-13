<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use App\Traits\HandlesMultipart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends BaseUserController
{
    use HandlesMultipart;
    public function __construct()
    {
        $this->model = Admin::class;
        $this->resource = AdminResource::class;
    }
    /**
     * Admin management controller — allows creating/updating admins and syncing permissions
     */
    // Create new admin with optional permissions array (permissions: [id, ...])
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name'  => 'required|string',
            'email'      => ['nullable','email', Rule::unique('users','email')],
            'phone'      => ['required','string', Rule::unique('users','phone')],
            'password'   => 'required|string|min:6',
            'personal_image' => 'sometimes|file|image|max:5120',
            'role_id'    => 'sometimes|nullable|integer|exists:roles,id',
        ]);

        // handle personal image upload (store on public disk)
        $personalImagePath = null;
        if ($request->hasFile('personal_image') && $request->file('personal_image')->isValid()) {
            $personalImagePath = $request->file('personal_image')->store('users', 'public');
        }

        DB::beginTransaction();
        try {
            // assign attributes directly to avoid mass-assignment / fillable issues
            $admin = new Admin();
            $admin->first_name = $validated['first_name'];
            $admin->last_name = $validated['last_name'];
            $admin->email = $validated['email'] ?? null;
            $admin->phone = $validated['phone'];
            $admin->password = Hash::make($validated['password']);
            $admin->status = 'active';
            // store relative path; AdminResource builds full URL
            $admin->personal_image = $personalImagePath;
            $admin->role_id = $validated['role_id'] ?? null;

            $admin->save(); // triggers model boot to set name/usertype

            DB::commit();

            $admin->load('role'); // eager load role for resource
            return response()->json(['message' => 'Admin created', 'admin' => new AdminResource($admin)], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to create admin', 'error' => $e->getMessage()], 500);
        }
    }

    // Update admin basic fields and optionally sync permissions
    public function update(Request $request, $id)
    {
        $this->handleMultipart($request);
        $admin = Admin::find($id);
        if (! $admin) {
            return response()->json(['message' => 'Admin not found'], 404);
        }

        $data = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'email' => 'sometimes|nullable|email|unique:users,email,' . $id,
            'phone' => 'sometimes|string|unique:users,phone,' . $id,
            'role_id'    => 'sometimes|nullable|integer|exists:roles,id',
            'personal_image' => 'sometimes|file|image|max:5120',
        ]);

        DB::beginTransaction();
        try {
            if (isset($data['first_name'])) $admin->first_name = $data['first_name'];
            if (isset($data['last_name'])) $admin->last_name = $data['last_name'];
            if (array_key_exists('email', $data)) $admin->email = $data['email'];
            if (array_key_exists('phone', $data)) $admin->phone = $data['phone'];

            // apply role change if provided (may be null to remove)
            if (array_key_exists('role_id', $data)) {
                $admin->role_id = $data['role_id'];
            }

            // handle personal_image upload on update
            if ($request->hasFile('personal_image') && $request->file('personal_image')->isValid()) {
                // delete old image if exists
                if ($admin->personal_image) {
                    $this->deleteStoredFile($admin->personal_image);
                }

                // store new file and get relative path
                $newPath = config('filesystems.disks.public.url') . '/' . $request->file('personal_image')->store('users', 'public');
                // force attribute change by setting to null first, then new path
                $admin->personal_image = $newPath;
                $admin->save();
            }

            $admin->save();
            DB::commit();

            // refresh from DB to ensure we have latest values
            $admin->refresh();
            $admin->load(['role']);
            return response()->json(['message' => 'Admin updated', 'admin' => new AdminResource($admin)]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to update admin', 'error' => $e->getMessage()], 500);
        }
    }

    // helper: delete a stored file given either a storage path or a public URL
    private function deleteStoredFile($urlOrPath)
    {
        if (! $urlOrPath) return;

        // If it's a full URL containing '/storage/', extract the relative path
        $storageSegment = '/storage/';
        if (strpos($urlOrPath, $storageSegment) !== false) {
            $relative = substr($urlOrPath, strpos($urlOrPath, $storageSegment) + strlen($storageSegment));
        } else {
            $relative = ltrim($urlOrPath, '/');
        }

        // delete from public disk
        if (Storage::disk('public')->exists($relative)) {
            Storage::disk('public')->delete($relative);
        }
    }
}
