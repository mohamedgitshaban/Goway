<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    // list roles
    public function index(Request $request)
    {
        $roles = Role::with('permissions')->orderBy('name_en')->get();

        $out = $roles->map(function ($role) use ($request) {
            $assigned = $role->permissions->pluck('id')->toArray();
            $permissionPayload = $this->buildPermissionsPayload($request, true, $assigned);
            return [
                'name_en' => $role->name_en,
                'name_ar' => $role->name_ar,
                'permissions' => $permissionPayload,
            ];
        });

        return response()->json(['status' => true, 'roles' => $out->values()]);
    }
        public function selectAllRoles()
    {
        $roles = Role::latest()->get();
        return response()->json(['status' => true, 'roles' => $roles]);
    }
    // create role with assigned permissions (array of permission ids)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'unique:roles,name_ar'],
            'name_en' => ['required', 'string', 'unique:roles,name_en'],
            'permissions' => 'array',
            'permissions.*.module_name' => 'sometimes|string',
            'permissions.*.permission_roles' => 'required|array',
            'permissions.*.permission_roles.*.id' => 'required|integer|exists:permissions,id',
            'permissions.*.permission_roles.*.assigned' => 'required|boolean',
        ]);

        $role = Role::create([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
        ]);

        // collect permission ids from grouped payload (only assigned === true)
        $permIds = [];
        foreach ($validated['permissions'] ?? [] as $group) {
            foreach ($group['permission_roles'] ?? [] as $r) {
                if (!isset($r['id']) || !is_numeric($r['id'])) continue;
                $assigned = array_key_exists('assigned', $r)
                    ? filter_var($r['assigned'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                    : true;
                if ($assigned) {
                    $permIds[] = (int)$r['id'];
                }
            }
        }

        if ($permIds) {
            $role->permissions()->sync(array_unique($permIds));
        }

        return response()->json(['status' => true, 'role' => $role->load('permissions')], 201);
    }

    // update role and its permissions
    public function update(Request $request, $id)
    {
        $role = Role::find($id);
        if (! $role) {
            return response()->json(['status' => false, 'message' => 'Role not found'], 404);
        }

        $validated = $request->validate([
            'name_ar' => ['sometimes', 'string', Rule::unique('roles', 'name_ar')->ignore($role->id)],
            'name_en' => ['sometimes', 'string', Rule::unique('roles', 'name_en')->ignore($role->id)],
            'permissions' => 'array',
            'permissions.*.permission_roles' => 'required|array',
            'permissions.*.permission_roles.*.id' => 'required|integer|exists:permissions,id',
            'permissions.*.permission_roles.*.assigned' => 'required|boolean',
        ]);

        if (isset($validated['name_ar'])) $role->name_ar = $validated['name_ar'];
        if (isset($validated['name_en'])) $role->name_en = $validated['name_en'];
        $role->save();

        $currentAssigned = $role->permissions->pluck('id')->toArray();
        $toAttach = [];
        $toDetach = [];

        foreach ($validated['permissions'] ?? [] as $group) {
            foreach ($group['permission_roles'] ?? [] as $r) {
                if (!isset($r['id']) || !is_numeric($r['id'])) continue;
                $permId = (int)$r['id'];
                $assigned = filter_var($r['assigned'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

                if ($assigned && !in_array($permId, $currentAssigned)) {
                    $toAttach[] = $permId;
                } elseif (!$assigned && in_array($permId, $currentAssigned)) {
                    $toDetach[] = $permId;
                }
            }
        }

        if ($toAttach) $role->permissions()->attach(array_unique($toAttach));
        if ($toDetach) $role->permissions()->detach(array_unique($toDetach));

        return response()->json(['status' => true, 'role' => $role]);
    }

    // soft-delete a role
    public function destroy($id)
    {
        $role = Role::find($id);
        if (! $role) {
            return response()->json(['status' => false, 'message' => 'Role not found'], 404);
        }

        $role->delete();

        return response()->json(['status' => true, 'message' => 'Role deleted']);
    }

    // restore a soft-deleted role
    public function restore($id)
    {
        $role = Role::withTrashed()->find($id);
        if (! $role) {
            return response()->json(['status' => false, 'message' => 'Role not found'], 404);
        }

        if (! method_exists($role, 'restore')) {
            return response()->json(['status' => false, 'message' => 'Restore not supported'], 400);
        }

        $role->restore();

        return response()->json(['status' => true, 'message' => 'Role restored']);
    }

    // show a single role with permissions (assigned flags)
    public function show(Request $request, $id)
    {
        $role = Role::with('permissions')->find($id);
        if (! $role) {
            return response()->json(['status' => false, 'message' => 'Role not found'], 404);
        }

        $assigned = $role->permissions->pluck('id')->toArray();
        $permissionPayload = $this->buildPermissionsPayload($request, true, $assigned);

        $out = [
            'name_en' => $role->name_en,
            'name_ar' => $role->name_ar,
            'permission' => $permissionPayload,
        ];

        return response()->json(['status' => true, 'role' => $out]);
    }

    // build permissions payload similar to AdminPermissionController
    private function buildPermissionsPayload(Request $request, $includeAssigned = false, $assignedIds = [])
    {
        $perms = Permission::orderBy('name')->get();

        $acceptHeader = $request->header('accept_lang') ?: $request->get('accept_lang') ?: $request->header('Accept-Language');
        $locale = app()->getLocale();
        if ($acceptHeader) {
            $parts = preg_split('/[;,]/', $acceptHeader);
            $locale = trim($parts[0]);
            if (strlen($locale) > 2) {
                $locale = strtolower(preg_split('/[-_]/', $locale)[0]);
            } else {
                $locale = strtolower($locale);
            }
        }

        try {
            app()->setLocale($locale);
        } catch (\Exception $e) {}

        $groups = [];
        foreach ($perms as $p) {
            $parts = explode('.', $p->name, 2);
            $module = $parts[0];
            $action = $parts[1] ?? null;
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
                $item = [
                    'id' => $permId,
                    'name' => $actionName,
                    'label' => $actionLabel,
                ];
                if ($includeAssigned) {
                    $item['is_active'] = in_array($permId, $assignedIds);
                }
                $roles[] = $item;
            }
            usort($roles, fn($a, $b) => strcmp($a['name'], $b['name']));
            $moduleKey = 'permissions.modules.' . $module;
            $moduleLabel = Lang::has($moduleKey) ? Lang::get($moduleKey) : $module;
            $result[] = [
                'module_name' => $module,
                'module_label' => $moduleLabel,
                'roles' => $roles,
            ];
        }

        usort($result, fn($a, $b) => strcmp($a['module_name'], $b['module_name']));

        return ['status' => true, 'permission_rules' => $result];
    }
}
