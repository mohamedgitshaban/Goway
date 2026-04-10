<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminPermissionController extends Controller
{
    // List all permissions
    public function index(Request $request)
    {
        $perms = Permission::orderBy('name')->get();
        return response()->json(['status' => true, 'permissions' => $perms]);
    }

    // Create a new permission
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|unique:permissions,name',
            'description' => 'nullable|string',
        ]);

        $perm = Permission::create($data);

        return response()->json(['status' => true, 'permission' => $perm], 201);
    }

    // Get a specific admin's permissions (returns all permissions with assigned flag)
    public function adminPermissions(Request $request, $adminId)
    {
        $admin = User::find($adminId);
        if (! $admin || ! $admin->isAdmin()) {
            return response()->json(['status' => false, 'message' => 'Admin not found'], 404);
        }

        $all = Permission::orderBy('name')->get();
        $assigned = $admin->permissions()->pluck('permissions.id')->toArray();

        $result = $all->map(function ($p) use ($assigned) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'assigned' => in_array($p->id, $assigned),
            ];
        });

        return response()->json(['status' => true, 'permissions' => $result]);
    }

    // Sync permissions for an admin
    public function syncAdminPermissions(Request $request, $adminId)
    {
        $admin = User::find($adminId);
        if (! $admin || ! $admin->isAdmin()) {
            return response()->json(['status' => false, 'message' => 'Admin not found'], 404);
        }

        $data = $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $permIds = $data['permissions'] ?? [];

        $admin->syncPermissions($permIds);

        return response()->json(['status' => true, 'message' => 'Permissions updated']);
    }
}
