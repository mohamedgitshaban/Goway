<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        //
    }

    public function render($request, Throwable $exception)
    {
        // 🔥 Handle validation errors globally
        if ($exception instanceof ValidationException) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $exception->errors(),
            ], 422);
        }
        if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'status'  => false,
                'message' => 'Route not found',
            ], 404);
        }
        if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'status'  => false,
                'message' => 'Method not allowed',
            ], 405);
        }
        if ($exception instanceof AuthenticationException) {
            return response()->json([
                'status'  => false,
                'message' => 'Not authenticated',
            ], 401);
        }
        if ($request->is('api/*')) {
            return response()->json([
                'status'  => false,
                'message' => $exception->getMessage(),
            ], 500);
        }

        return parent::render($request, $exception);
    }
}
