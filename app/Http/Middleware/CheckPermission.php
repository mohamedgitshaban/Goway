<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Permission;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Usage: ->middleware('permission:permission_name,edit')
     * If edit param is provided, check can_edit true.
     */
    public function handle(Request $request, Closure $next, string $permissionName, string $mode = null)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], Response::HTTP_UNAUTHORIZED);
        }

        // Only admins / super_admin have permission rows
        if (! in_array($user->usertype, [\App\Models\User::ROLE_ADMIN], true)) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], Response::HTTP_FORBIDDEN);
        }

        $permission = Permission::where('name', $permissionName)->first();

        if (! $permission) {
            return response()->json(['message' => 'Permission not found'], Response::HTTP_FORBIDDEN);
        }

    $adminPermission = $user->adminPermissions()->where('permission_id', $permission->id)->first();

        if (! $adminPermission) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], Response::HTTP_FORBIDDEN);
        }

        if ($mode === 'edit' && ! $adminPermission->can_edit) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
