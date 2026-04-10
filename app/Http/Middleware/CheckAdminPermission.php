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

        if ($user->hasPermission($permission)) {
            return $next($request);
        }

        return response()->json(['status' => false, 'message' => 'Forbidden: missing permission'], 403);
    }
}
