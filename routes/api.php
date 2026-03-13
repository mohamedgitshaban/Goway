<?php

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
Route::prefix('driver')->middleware(['auth:sanctum','role:driver','usertype'])->group(function () {
        Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
        Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    });
});

// Client-specific grouped endpoints
Route::prefix('client')->middleware(['auth:sanctum','role:client','usertype'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
        Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    });
});

// Admin-only routes with permission checks
Route::prefix('admin')->middleware(['auth:sanctum','role:admin',])->group(function () {
    // Admin profile & logout

    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
        Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);
    });
    // Route::apiResource('clients',);
    // Route::apiResource('driver',);
    // Route::apiResource('admins',);
    // Trip types management (permission: manage_trip_types used at route level)
    
});