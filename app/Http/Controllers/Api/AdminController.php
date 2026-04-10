<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6',
            'personal_image' => 'sometimes|file|image|max:5120',
            'role_id' => 'sometimes|nullable|integer|exists:roles,id',
        ]);

        // handle personal image upload (store on public disk)
        $personalImagePath = null;
        if ($request->hasFile('personal_image') && $request->file('personal_image')->isValid()) {
            $personalImagePath = $request->file('personal_image')->store('users', 'public');
        }

        $admin = Admin::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'personal_image' => $personalImagePath,
            'role_id' => $data['role_id'] ?? null,
        ]);

        return response()->json(['message' => 'Admin created', 'admin' => new AdminResource($admin)], 201);
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
            'permissions' => 'sometimes|array',
            'permissions.*.module_name' => 'sometimes|string',
            'permissions.*.roles' => 'sometimes|array',
            'permissions.*.roles.*.id' => 'sometimes|integer|exists:permissions,id',
            'permissions.*.roles.*.assigned' => 'required|boolean',
            'personal_image' => 'sometimes|file|image|max:5120',
        ]);

        if (isset($data['first_name'])) $admin->first_name = $data['first_name'];
        if (isset($data['last_name'])) $admin->last_name = $data['last_name'];
        if (array_key_exists('email', $data)) $admin->email = $data['email'];
        if (array_key_exists('phone', $data)) $admin->phone = $data['phone'];

        $admin->save();

        // handle personal_image upload on update
        if ($request->hasFile('personal_image') && $request->file('personal_image')->isValid()) {
            // delete old image if exists
            if ($admin->personal_image) {
                Storage::disk('public')->delete($admin->personal_image);
            }

            $path = $request->file('personal_image')->store('users', 'public');
            $admin->personal_image = $path;
            $admin->save();
        }

        return response()->json(['message' => 'Admin updated', 'admin' => new AdminResource($admin)]);
    }
}
