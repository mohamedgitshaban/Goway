<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure the authenticated user has one of the required roles.
 * Usage in routes: ->middleware('role:admin') or ->middleware('role:driver,client')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], Response::HTTP_UNAUTHORIZED);
        }

        // If no specific roles required, allow
        if (empty($roles)) {
            return $next($request);
        }

        // Normalize roles
        $roles = array_map('strval', $roles);

        // allow super_admin to pass any role check
        if ($user->usertype === \App\Models\User::ROLE_SUPER_ADMIN) {
            return $next($request);
        }

        if (! in_array($user->usertype, $roles, true)) {
            return response()->json(['message' => 'Forbidden - insufficient role'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
