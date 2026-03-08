<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Admin authentication endpoints
Route::post('/admin/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
Route::post('/admin/register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
Route::post('/admin/forgot-password', [\App\Http\Controllers\Api\AuthController::class, 'forgotPassword']);
Route::post('/admin/reset-password', [\App\Http\Controllers\Api\AuthController::class, 'resetPassword']);

// Client-specific authentication endpoints
Route::post('/client/login', [\App\Http\Controllers\Api\ClientAuthController::class, 'login']);
Route::post('/client/register', [\App\Http\Controllers\Api\ClientAuthController::class, 'register']);
Route::post('/client/forgot-password', [\App\Http\Controllers\Api\ClientAuthController::class, 'forgotPassword']);
Route::post('/client/reset-password', [\App\Http\Controllers\Api\ClientAuthController::class, 'resetPassword']);

// Driver-specific authentication endpoints
Route::post('/driver/login', [\App\Http\Controllers\Api\DriverAuthController::class, 'login']);
Route::post('/driver/register', [\App\Http\Controllers\Api\DriverAuthController::class, 'register']);
Route::post('/driver/forgot-password', [\App\Http\Controllers\Api\DriverAuthController::class, 'forgotPassword']);
Route::post('/driver/reset-password', [\App\Http\Controllers\Api\DriverAuthController::class, 'resetPassword']);

// NOTE: Protected endpoints for driver and client live under their respective prefixes (/driver, /client).

// Driver-specific grouped endpoints
Route::prefix('driver')->middleware(['auth:sanctum','role:driver','usertype'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
    Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);

    Route::get('/wallets', [\App\Http\Controllers\Api\WalletController::class, 'index']);
    Route::get('/wallets/{id}', [\App\Http\Controllers\Api\WalletController::class, 'show']);
    Route::post('/wallets/transaction', [\App\Http\Controllers\Api\WalletController::class, 'transaction']);

    Route::get('/trip-types', [\App\Http\Controllers\Api\TripTypeController::class, 'index']);
    Route::post('/reports', [\App\Http\Controllers\Api\ReportController::class, 'store']);
});

// Client-specific grouped endpoints
Route::prefix('client')->middleware(['auth:sanctum','role:client','usertype'])->group(function () {
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
    Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);

    Route::get('/wallets', [\App\Http\Controllers\Api\WalletController::class, 'index']);
    Route::get('/wallets/{id}', [\App\Http\Controllers\Api\WalletController::class, 'show']);
    Route::post('/wallets/transaction', [\App\Http\Controllers\Api\WalletController::class, 'transaction']);

    Route::get('/trip-types', [\App\Http\Controllers\Api\TripTypeController::class, 'index']);
    Route::post('/reports', [\App\Http\Controllers\Api\ReportController::class, 'store']);
});

// Admin-only routes with permission checks
Route::prefix('admin')->middleware(['auth:sanctum','role:admin',])->group(function () {
    // Admin profile & logout
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);
    Route::get('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'me']);
    Route::put('/profile', [\App\Http\Controllers\Api\ProfileController::class, 'update']);

    // Trip types management (permission: manage_trip_types used at route level)
    Route::post('/trip-types', [\App\Http\Controllers\Api\TripTypeController::class, 'store'])->middleware('permission:manage_trip_types,edit');
    Route::put('/trip-types/{id}', [\App\Http\Controllers\Api\TripTypeController::class, 'update'])->middleware('permission:manage_trip_types,edit');
    Route::delete('/trip-types/{id}', [\App\Http\Controllers\Api\TripTypeController::class, 'destroy'])->middleware('permission:manage_trip_types,edit');

    // Trip types list/show for admin
    Route::get('/trip-types/{id}', [\App\Http\Controllers\Api\TripTypeController::class, 'show']);

    // Reports view
    Route::get('/reports', [\App\Http\Controllers\Api\ReportController::class, 'index'])->middleware('permission:view_reports');

    // Admin: view wallets for a specific user
    Route::get('/users/{id}/wallets', [\App\Http\Controllers\Api\WalletController::class, 'userWallets'])->middleware('permission:view_wallets');
});
