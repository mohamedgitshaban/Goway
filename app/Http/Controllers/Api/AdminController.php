<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends BaseUserController
{
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

        // handle personal image upload (store on public disk) — returns public URL
        $personalImageUrl = null;
        if ($request->hasFile('personal_image') && $request->file('personal_image')->isValid()) {
            $path = $request->file('personal_image')->store('users', 'public');
            $personalImageUrl = Storage::disk('public')->url($path);
        }

        DB::beginTransaction();
        try {
            $admin = Admin::create([
                'first_name'     => $validated['first_name'],
                'last_name'      => $validated['last_name'],
                'email'          => $validated['email'] ?? null,
                'phone'          => $validated['phone'],
                'password'       => Hash::make($validated['password']),
                'status'         => 'active',
                'personal_image' => $personalImageUrl,
                'role_id'        => $validated['role_id'] ?? null,
            ]);

            // ensure role relation is set (in case model doesn't auto-fill)
            if (! empty($validated['role_id'])) {
                $admin->role_id = $validated['role_id'];
                $admin->save();
            }

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
            // delete old image if exists (supports stored path or full URL)
            if ($admin->personal_image) {
                $this->deleteStoredFile($admin->personal_image);
            }

            $path = $request->file('personal_image')->store('users', 'public');
            // store as public URL (consistent with store())
            $admin->personal_image = Storage::disk('public')->url($path);
        }

        $admin->save();

        $admin->load('role');

        return response()->json(['message' => 'Admin updated', 'admin' => new AdminResource($admin)]);
    }

    // helper: delete a stored file given either a storage path or a public URL
    private function deleteStoredFile($urlOrPath)
    {
        if (! $urlOrPath) return;

        // If it's a full URL containing '/storage/', extract the relative path after '/storage/'
        $storageSegment = '/storage/';
        if (strpos($urlOrPath, $storageSegment) !== false) {
            $relative = substr($urlOrPath, strpos($urlOrPath, $storageSegment) + strlen($storageSegment));
        } else {
            // assume it's already a relative storage path
            $relative = ltrim($urlOrPath, '/');
        }

        // delete if exists
        try {
            Storage::disk('public')->delete($relative);
        } catch (\Throwable $e) {
            // ignore errors deleting (file may not exist)
        }
    }
}
