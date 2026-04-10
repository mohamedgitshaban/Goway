<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdminPermission
{
    /**
     * Handle an incoming request.
     * Usage: ->middleware('admin.permission:clients.index')
     */
    public function handle(Request $request, Closure $next, $permission = null)
    {
        $user = $request->user();

        if (! $user || ! method_exists($user, 'isAdmin') || ! $user->isAdmin()) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        if (! $permission) {
            // no specific permission provided, allow
            return $next($request);
        }

        // Check permission via role
        if (method_exists($user, 'role') && $user->role) {
            $rolePermissions = $user->role->permissions->pluck('name')->toArray();
            if (in_array($permission, $rolePermissions)) {
                return $next($request);
            }
        }

        // Fallback to user-level permission check if available
        if (method_exists($user, 'hasPermission') && $user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json(['status' => false, 'message' => 'Forbidden: missing permission'], 403);
    }
}
