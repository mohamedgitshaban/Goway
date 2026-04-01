<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUsertypeHeader
{
    /**
     * Ensure `usertype` header exists and matches authenticated user.
     */
    public function handle(Request $request, Closure $next)
    {
        $header = $request->header('usertype');

        if (! $header) {
            return response()->json(['message' => 'Header `usertype` is required'], Response::HTTP_BAD_REQUEST);
        }

        $user = $request->user();

        if ($user && $user->usertype !== $header) {
            return response()->json(['message' => 'غير متاح لهذا المستخدم'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
