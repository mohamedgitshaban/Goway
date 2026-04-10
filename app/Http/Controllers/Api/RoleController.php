<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleResource;
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
        $limit = (int) $request->input('limit', 15);

        $roles = Role::with('permissions')
            ->orderBy('name_en')
            ->paginate($limit);

        // Attach computed permission payload to each role model in the paginator collection
        $roles->getCollection()->transform(function ($role) use ($request) {
            $assigned = $role->permissions->pluck('id')->toArray();
            $role->permission_payload = $this->buildPermissionsPayload($request, true, $assigned);
            return $role;
        });

        // Return a Resource collection (will serialize to { data, links, meta })
        return RoleResource::collection($roles);
    }

    // return all permissions grouped (no assigned flags) for role creation UI
    public function allPermissions(Request $request)
    {
        // buildPermissionsPayload will return ['status' => true, 'permission_rules' => [...]]
        $payload = $this->buildPermissionsPayload($request, false);
        return response()->json($payload);
    }

    public function selectAllRoles()
    {
        $roles = Role::latest()->get();
        return response()->json(['status' => true, 'data' => $roles]);
    }
    // create role with assigned permissions (array of permission ids or grouped payload)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'unique:roles,name_ar'],
            'name_en' => ['required', 'string', 'unique:roles,name_en'],
            'permissions' => 'array', // accept either grouped or flat array
        ]);

        $role = Role::create([
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'],
        ]);

        // collect permission ids from grouped payload OR flat payload
        $permIds = $this->collectAssignedPermissionIds($request->input('permissions', []));

        // validate permission ids exist
        if (! empty($permIds)) {
            $existing = \App\Models\Permission::whereIn('id', $permIds)->pluck('id')->toArray();
            $invalid = array_diff($permIds, $existing);
            if (! empty($invalid)) {
                return response()->json(['status' => false, 'message' => 'Invalid permission ids', 'invalid' => array_values($invalid)], 422);
            }
        }

        if (! empty($permIds)) {
            $role->permissions()->sync(array_values(array_unique($permIds)));
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
            'permissions' => 'array', // accept grouped or flat
        ]);

        if (isset($validated['name_ar'])) $role->name_ar = $validated['name_ar'];
        if (isset($validated['name_en'])) $role->name_en = $validated['name_en'];
        $role->save();

        // current assigned
        $currentAssigned = $role->permissions->pluck('id')->toArray();
        $toAttach = [];
        $toDetach = [];

        $permissionsPayload = $request->input('permissions', []);

        // detect grouped format (permissions.*.permission_roles)
        $isGrouped = false;
        if (! empty($permissionsPayload)) {
            $first = $permissionsPayload[array_key_first($permissionsPayload)];
            if (is_array($first) && array_key_exists('permission_roles', $first)) {
                $isGrouped = true;
            }
        }

        if ($isGrouped) {
            // existing grouped logic: iterate groups and apply assigned flags
            foreach ($permissionsPayload as $group) {
                foreach ($group['permission_roles'] ?? [] as $r) {
                    if (!isset($r['id']) || !is_numeric($r['id'])) continue;
                    $permId = (int)$r['id'];
                    $assigned = array_key_exists('assigned', $r)
                        ? filter_var($r['assigned'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
                        : true;

                    if ($assigned && !in_array($permId, $currentAssigned)) {
                        $toAttach[] = $permId;
                    } elseif ($assigned === false && in_array($permId, $currentAssigned)) {
                        $toDetach[] = $permId;
                    }
                }
            }
        } else {
            // flat payload: can be array of ids OR array of {id, assigned}
            $desiredAssigned = [];
            $explicitFalse = [];

            foreach ($permissionsPayload as $p) {
                if (is_numeric($p)) {
                    $desiredAssigned[] = (int)$p;
                } elseif (is_array($p) || is_object($p)) {
                    $r = (array) $p;
                    if (! isset($r['id']) || ! is_numeric($r['id'])) continue;
                    $permId = (int) $r['id'];
                    if (array_key_exists('assigned', $r)) {
                        $assigned = filter_var($r['assigned'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($assigned) {
                            $desiredAssigned[] = $permId;
                        } else {
                            $explicitFalse[] = $permId;
                        }
                    } else {
                        // if no assigned flag, treat as desired assigned
                        $desiredAssigned[] = $permId;
                    }
                }
            }

            // validate ids exist
            $allIds = array_values(array_unique(array_merge($desiredAssigned, $explicitFalse)));
            if (! empty($allIds)) {
                $existing = \App\Models\Permission::whereIn('id', $allIds)->pluck('id')->toArray();
                $invalid = array_diff($allIds, $existing);
                if (! empty($invalid)) {
                    return response()->json(['status' => false, 'message' => 'Invalid permission ids', 'invalid' => array_values($invalid)], 422);
                }
            }

            // If client sent explicit flags (objects) we act per flags:
            if (! empty($desiredAssigned) || ! empty($explicitFalse)) {
                // attach desiredAssigned not currently assigned
                $toAttach = array_diff($desiredAssigned, $currentAssigned);
                // detach explicit false ones that are currently assigned
                $toDetach = array_intersect($explicitFalse, $currentAssigned);
            } else {
                // client sent flat list of ids (possibly empty) -> treat as desired final set
                $desiredAssigned = array_values(array_unique($desiredAssigned));
                $toAttach = array_diff($desiredAssigned, $currentAssigned);
                $toDetach = array_diff($currentAssigned, $desiredAssigned);
            }
        }

        if (! empty($toAttach)) $role->permissions()->attach(array_values(array_unique($toAttach)));
        if (! empty($toDetach)) $role->permissions()->detach(array_values(array_unique($toDetach)));

        return response()->json(['status' => true, 'role' => $role->load('permissions')]);
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
        $role->permission_payload = $this->buildPermissionsPayload($request, true, $assigned);

        // return single resource
        return new RoleResource($role);
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
        } catch (\Exception $e) {
        }

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

    /**
     * Helper: collect assigned permission ids from grouped or flat payload
     */
    private function collectAssignedPermissionIds($permissionsPayload)
    {
        $permIds = [];

        // grouped?
        $isGrouped = false;
        if (! empty($permissionsPayload)) {
            $first = $permissionsPayload[array_key_first($permissionsPayload)];
            if (is_array($first) && array_key_exists('permission_roles', $first)) {
                $isGrouped = true;
            }
        }

        if ($isGrouped) {
            foreach ($permissionsPayload as $group) {
                foreach ($group['permission_roles'] ?? [] as $r) {
                    if (! isset($r['id']) || ! is_numeric($r['id'])) continue;
                    if (array_key_exists('assigned', $r)) {
                        $assigned = filter_var($r['assigned'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($assigned === true) {
                            $permIds[] = (int) $r['id'];
                        }
                    } else {
                        // backwards-compatible: include if no assigned flag
                        $permIds[] = (int) $r['id'];
                    }
                }
            }
        } else {
            // flat array: numeric ids or objects
            foreach ($permissionsPayload as $p) {
                if (is_numeric($p)) {
                    $permIds[] = (int) $p;
                } elseif (is_array($p) || is_object($p)) {
                    $r = (array) $p;
                    if (! isset($r['id']) || ! is_numeric($r['id'])) continue;
                    if (array_key_exists('assigned', $r)) {
                        $assigned = filter_var($r['assigned'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                        if ($assigned === true) {
                            $permIds[] = (int) $r['id'];
                        }
                    } else {
                        $permIds[] = (int) $r['id'];
                    }
                }
            }
        }

        return array_values(array_unique($permIds));
    }
}
