<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DriverController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('driver/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\DriverAuthController::class, 'login']);
    Route::post('/send_otp', [\App\Http\Controllers\Api\DriverAuthController::class, 'send_otp']);
    Route::post('/register', [\App\Http\Controllers\Api\DriverAuthController::class, 'register']);
    Route::post('/activate_phone', [\App\Http\Controllers\Api\DriverAuthController::class, 'activatePhone']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\DriverAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\DriverAuthController::class, 'resetPassword']);
});

Route::prefix('client/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\ClientAuthController::class, 'login']);
    Route::post('/send_otp', [\App\Http\Controllers\Api\ClientAuthController::class, 'send_otp']);
    Route::post('/register', [\App\Http\Controllers\Api\ClientAuthController::class, 'register']);
    Route::post('/activate_phone', [\App\Http\Controllers\Api\ClientAuthController::class, 'activatePhone']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\ClientAuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\ClientAuthController::class, 'resetPassword']);
});

Route::prefix('admin/auth')->group(function () {
    Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
    Route::post('/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);
});
// Driver-specific grouped endpoints
Route::prefix('driver')->middleware(['auth:sanctum', 'role:driver', 'usertype'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\DriverAuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\DriverAuthController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\DriverAuthController::class, 'updateProfile']);
    });
});

// Client-specific grouped endpoints
Route::prefix('client')->middleware(['auth:sanctum', 'role:client', 'usertype'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\ClientAuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\ClientAuthController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\ClientAuthController::class, 'updateProfile']);
    });
});

// Admin-only routes with permission checks
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin',])->group(function () {
    // Admin profile & logout

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\AuthController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    });

    Route::prefix('clients')->group(function () {
        Route::get('/', [ClientController::class, 'index']);          // list + search + filter + sort + pagination
        Route::get('/export', [ClientController::class, 'export']);   // export CSV / Excel
        Route::get('/{id}', [ClientController::class, 'show']);       // show single
        Route::put('/{id}/activate', [ClientController::class, 'activate']);
        Route::put('/{id}/suspend', [ClientController::class, 'suspend']);
        Route::delete('/{id}', [ClientController::class, 'destroy']); // soft delete
        Route::put('/{id}/restore', [ClientController::class, 'restore']); // restore
    });

    Route::prefix('drivers')->group(function () {
        Route::get('/', [DriverController::class, 'index']);
        Route::get('/export', [DriverController::class, 'export']);
        Route::get('/{id}', [DriverController::class, 'show']);
        Route::put('/{id}/activate', [DriverController::class, 'activate']);
        Route::put('/{id}/suspend', [DriverController::class, 'suspend']);
        Route::delete('/{id}', [DriverController::class, 'destroy']);
        Route::put('/{id}/restore', [DriverController::class, 'restore']);
    });

    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminController::class, 'index']);
        Route::get('/export', [AdminController::class, 'export']);
        Route::get('/{id}', [AdminController::class, 'show']);
        Route::put('/{id}/activate', [AdminController::class, 'activate']);
        Route::put('/{id}/suspend', [AdminController::class, 'suspend']);
        Route::delete('/{id}', [AdminController::class, 'destroy']);
        Route::put('/{id}/restore', [AdminController::class, 'restore']);
    });
});
