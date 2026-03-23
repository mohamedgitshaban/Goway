<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\TripTypeController;
use App\Http\Controllers\Api\WalletController;
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
Route::prefix('driver')->middleware(['auth:sanctum', 'usertype'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\DriverAuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\DriverAuthController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\DriverAuthController::class, 'updateProfile']);
    });
    Route::prefix('documents')->group(function () {
        Route::get('trip_types', [\App\Http\Controllers\Api\TripTypeController::class, 'index']);
        Route::post('/upload', [\App\Http\Controllers\Api\DriverDocumentController::class, 'uploadDocuments']);
        Route::get('/', [\App\Http\Controllers\Api\DriverDocumentController::class, 'index']);
    });
});

// Client-specific grouped endpoints
Route::prefix('client')->middleware(['auth:sanctum', 'usertype'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\ClientAuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\ClientAuthController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\ClientAuthController::class, 'updateProfile']);
    });
});

// Admin-only routes with permission checks
Route::prefix('admin')->middleware(['auth:sanctum', 'usertype',])->group(function () {
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
        Route::put('/{id}/status-toggle', [ClientController::class, 'statusToggle']);
        Route::delete('/{id}', [ClientController::class, 'destroy']); // soft delete
        Route::put('/{id}/restore', [ClientController::class, 'restore']); // restore
    });

    Route::prefix('drivers')->group(function () {
        Route::get('/', [DriverController::class, 'index']);
        Route::get('/export', [DriverController::class, 'export']);
        Route::get('/{id}', [DriverController::class, 'show']);
        Route::put('/{id}/activate', [DriverController::class, 'activate']);
        Route::put('/{id}/suspend', [DriverController::class, 'suspend']);
        Route::put('/{id}/status-toggle', [DriverController::class, 'statusToggle']);
        Route::delete('/{id}', [DriverController::class, 'destroy']);

        Route::put('/{id}/restore', [DriverController::class, 'restore']);
    });

    Route::prefix('admins')->group(function () {
        Route::get('/', [AdminController::class, 'index']);
        Route::get('/export', [AdminController::class, 'export']);
        Route::get('/{id}', [AdminController::class, 'show']);
        Route::put('/{id}/activate', [AdminController::class, 'activate']);
        Route::put('/{id}/suspend', [AdminController::class, 'suspend']);
        Route::put('/{id}/status-toggle', [AdminController::class, 'statusToggle']);
        Route::delete('/{id}', [AdminController::class, 'destroy']);
        Route::put('/{id}/restore', [AdminController::class, 'restore']);
    });
    Route::prefix('trip_types')->group(function () {
        Route::get('/', [TripTypeController::class, 'index']);
        Route::get('/export', [TripTypeController::class, 'export']);
        Route::post('/', [TripTypeController::class, 'store']);
        Route::put('/{id}', [TripTypeController::class, 'update']);
        Route::get('/{id}', [TripTypeController::class, 'show']);
        Route::put('/{id}/activate', [TripTypeController::class, 'activate']);
        Route::put('/{id}/suspend', [TripTypeController::class, 'suspend']);
        Route::put('/{id}/status-toggle', [TripTypeController::class, 'statusToggle']);
        Route::put('/{id}/licence-toggle', [TripTypeController::class, 'licenceToggle']);
        Route::delete('/{id}', [TripTypeController::class, 'destroy']);
        Route::put('/{id}/restore', [TripTypeController::class, 'restore']);
    });
    Route::prefix('wallets')->group(function () {
        Route::get('/', [WalletController::class, 'index']);
        Route::get('/{id}', [WalletController::class, 'show']);
    });
});
