<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AdminResource;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $admin = Admin::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'status' => 'active',
        ]);

        if (! empty($data['permissions'])) {
            $admin->syncPermissions($data['permissions']);
        }

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
            'password' => 'sometimes|nullable|string|min:6',
            'permissions' => 'sometimes|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        if (isset($data['first_name'])) $admin->first_name = $data['first_name'];
        if (isset($data['last_name'])) $admin->last_name = $data['last_name'];
        if (array_key_exists('email', $data)) $admin->email = $data['email'];
        if (array_key_exists('phone', $data)) $admin->phone = $data['phone'];
        if (! empty($data['password'])) $admin->password = Hash::make($data['password']);

        $admin->save();

        if (array_key_exists('permissions', $data)) {
            $admin->syncPermissions($data['permissions'] ?? []);
        }

        return response()->json(['message' => 'Admin updated', 'admin' => new AdminResource($admin)]);
    }
}
