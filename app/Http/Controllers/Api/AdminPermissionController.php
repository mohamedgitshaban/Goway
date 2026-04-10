<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Lang;

class AdminPermissionController extends Controller
{
    // List all permissions
    public function index(Request $request)
    {
        $perms = Permission::orderBy('name')->get();

        // Determine requested locale: prefer custom 'accept_lang' header or query param,
        // otherwise check standard 'Accept-Language' header and fall back to app locale.
        $acceptHeader = $request->header('accept_lang') ?: $request->get('accept_lang') ?: $request->header('Accept-Language');

        if ($acceptHeader) {
            // Accept-Language may contain values like 'ar,en;q=0.9'. Take first locale token.
            $parts = preg_split('/[;,]/', $acceptHeader);
            $locale = isset($parts[0]) ? trim($parts[0]) : app()->getLocale();
            // normalize to short locale like 'ar' when possible
            if (strlen($locale) > 2 && strpos($locale, '-') !== false) {
                $locale = strtolower(explode('-', $locale)[0]);
            } elseif (strlen($locale) > 2 && strpos($locale, '_') !== false) {
                $locale = strtolower(explode('_', $locale)[0]);
            }
            $locale = strtolower($locale);
        } else {
            $locale = app()->getLocale();
        }

        // set application locale so Lang::get/has without explicit locale will use it
        try {
            app()->setLocale($locale);
        } catch (\Exception $e) {
            // ignore and continue with default locale
        }

        // Group permissions by module (part before first dot) and collect actions with ids
        $groups = [];
        foreach ($perms as $p) {
            $parts = explode('.', $p->name, 2);
            $module = $parts[0];
            $action = isset($parts[1]) ? $parts[1] : null;

            if (! isset($groups[$module])) {
                $groups[$module] = [];
            }

            // store action => id
            if ($action) {
                $groups[$module][$action] = $p->id;
            }
        }

        $result = [];
        foreach ($groups as $module => $actions) {
            $roles = [];
            foreach ($actions as $actionName => $permId) {
                $actionKey = 'permissions.actions.' . $actionName;
                $actionLabel = Lang::has($actionKey) ? Lang::get($actionKey) : $actionName;

                $roles[] = ['id' => $permId, 'name' => $actionName, 'label' => $actionLabel];
            }

            // sort roles by name
            usort($roles, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });

            $moduleKey = 'permissions.modules.' . $module;
            $moduleLabel = Lang::has($moduleKey) ? Lang::get($moduleKey) : $module;

            $result[] = [
                'module_name' => $module,
                'module_label' => $moduleLabel,
                'roles' => $roles,
            ];
        }

        // Sort modules alphabetically by module_name
        usort($result, function ($a, $b) {
            return strcmp($a['module_name'], $b['module_name']);
        });

        return response()->json(['status' => true, 'permissions' => $result]);
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
