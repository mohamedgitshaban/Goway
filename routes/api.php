<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\ClientTripController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\DriverTripController;
use App\Http\Controllers\Api\FavoriteLocationController;
use App\Http\Controllers\Api\TripTypeController;
use App\Http\Controllers\Api\UserCouponController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\VehicleModelController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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
        Route::put('/goOnline', [\App\Http\Controllers\Api\DriverAuthController::class, 'goOnline']);
        Route::put('/goOffline', [\App\Http\Controllers\Api\DriverAuthController::class, 'goOffline']);
        Route::put('/toggleonlinestatus', [\App\Http\Controllers\Api\DriverAuthController::class, 'toggleonlinestatus']);
    });
    Route::get('/offers', [\App\Http\Controllers\Api\OfferController::class, 'index']);
    Route::prefix("vehicle")->group(function () {
        Route::get('{id}/brands', [VehicleModelController::class, 'brands']);
        Route::get('{id}/models', [VehicleModelController::class, 'brandModels']);
    });
    Route::prefix('documents')->group(function () {
        Route::get('trip_types', [\App\Http\Controllers\Api\TripTypeController::class, 'index']);
        Route::post('/upload', [\App\Http\Controllers\Api\DriverDocumentController::class, 'uploadDocuments']);
        Route::get('/', [\App\Http\Controllers\Api\DriverDocumentController::class, 'index']);
    });
    Route::post('/location', [\App\Http\Controllers\Api\DriverLocationController::class, 'update']);
    Route::post('/trips/{trip}/accept', [\App\Http\Controllers\Api\DriverTripController::class, 'accept']);
    Route::post('/trips/{trip}/arrived', [\App\Http\Controllers\Api\DriverTripController::class, 'arrived']);
    Route::post('/trips/{trip}/start', [\App\Http\Controllers\Api\DriverTripController::class, 'start']);
    Route::post('/trips/{trip}/complete', [\App\Http\Controllers\Api\DriverTripController::class, 'complete']);
    Route::post('/trips/{trip}/cancel', [DriverTripController::class, 'cancel']);
    Route::post('/driver/trips/{trip}/negotiate', [DriverTripController::class, 'negotiate']);
    Route::post('/driver/trips/{trip}/rate', [DriverTripController::class, 'rateClient']);
    Route::get('/trips', [\App\Http\Controllers\Api\DriverTripController::class, 'index']);
});
Route::prefix('client')->middleware(['auth:sanctum', 'usertype'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [\App\Http\Controllers\Api\ClientAuthController::class, 'logout']);
        Route::get('/profile', [\App\Http\Controllers\Api\ClientAuthController::class, 'profile']);
        Route::put('/profile', [\App\Http\Controllers\Api\ClientAuthController::class, 'updateProfile']);
    });
    Route::apiResource('favorite-locations', FavoriteLocationController::class);
    Route::get('/offers', [\App\Http\Controllers\Api\OfferController::class, 'index']);
    Route::get('/coupons', [UserCouponController::class, 'allCoupons']);
    Route::get('/nearby-drivers', [\App\Http\Controllers\Api\ClientNearbyDriversController::class, 'index']);
    Route::post('/trips/estimate', [\App\Http\Controllers\Api\ClientTripController::class, 'estimate']);
    Route::post('/trips', [\App\Http\Controllers\Api\ClientTripController::class, 'store']);
    Route::post('/trips/{trip}/cancel', [\App\Http\Controllers\Api\ClientTripController::class, 'cancel']);
    Route::post('/trips/{trip}/negotiate/accept', [ClientTripController::class, 'acceptNegotiation']);
    Route::post('/trips/{trip}/negotiate/reject', [ClientTripController::class, 'rejectNegotiation']);
    Route::post('/trips/{trip}/negotiate/counter', [ClientTripController::class, 'counterNegotiation']);
    Route::post('/trips/{trip}/rate', [ClientTripController::class, 'rateDriver']);
    Route::get('/trips', [\App\Http\Controllers\Api\ClientTripController::class, 'index']);
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
        Route::get('/', [ClientController::class, 'index'])->middleware('admin.permission:clients.index');          // list + search + filter + sort + pagination
        Route::get('/export', [ClientController::class, 'export'])->middleware('admin.permission:clients.export');   // export CSV / Excel
        Route::get('/{id}', [ClientController::class, 'show'])->middleware('admin.permission:clients.show');       // show single
        Route::put('/{id}/activate', [ClientController::class, 'activate'])->middleware('admin.permission:clients.activate');
        Route::put('/{id}/suspend', [ClientController::class, 'suspend'])->middleware('admin.permission:clients.suspend');
        Route::put('/{id}/status-toggle', [ClientController::class, 'statusToggle'])->middleware('admin.permission:clients.status_toggle');
        Route::delete('/{id}', [ClientController::class, 'destroy'])->middleware('admin.permission:clients.destroy'); // soft delete
        Route::put('/{id}/restore', [ClientController::class, 'restore'])->middleware('admin.permission:clients.restore'); // restore
    });

    Route::prefix('drivers')->group(function () {
        Route::get('/', [DriverController::class, 'index'])->middleware('admin.permission:drivers.index');
        Route::get('/export', [DriverController::class, 'export'])->middleware('admin.permission:drivers.export');
        Route::get('/{id}', [DriverController::class, 'show'])->middleware('admin.permission:drivers.show');
        Route::put('/{id}/activate', [DriverController::class, 'activate'])->middleware('admin.permission:drivers.activate');
        Route::put('/{id}/suspend', [DriverController::class, 'suspend'])->middleware('admin.permission:drivers.suspend');
        Route::put('/{id}/status-toggle', [DriverController::class, 'statusToggle'])->middleware('admin.permission:drivers.status_toggle');
        Route::delete('/{id}', [DriverController::class, 'destroy'])->middleware('admin.permission:drivers.destroy');

        Route::put('/{id}/restore', [DriverController::class, 'restore'])->middleware('admin.permission:drivers.restore');
        // Create / update admin-managed drivers (if needed)
    });

    Route::prefix('admins')->group(function () {
        // create an admin and update admin
        Route::post('/', [AdminController::class, 'store'])->middleware('admin.permission:admins.store');
        // use PUT for updates and parse multipart bodies via middleware
        Route::put('/{id}', [AdminController::class, 'update'])
            ->middleware(['admin.permission:admins.update']);
        Route::get('/', [AdminController::class, 'index'])->middleware('admin.permission:admins.index');
        Route::get('/export', [AdminController::class, 'export'])->middleware('admin.permission:admins.export');
        Route::get('/{id}', [AdminController::class, 'show'])->middleware('admin.permission:admins.show');
        Route::put('/{id}/activate', [AdminController::class, 'activate'])->middleware('admin.permission:admins.activate');
        Route::put('/{id}/suspend', [AdminController::class, 'suspend'])->middleware('admin.permission:admins.suspend');
        Route::put('/{id}/status-toggle', [AdminController::class, 'statusToggle'])->middleware('admin.permission:admins.status_toggle');
        Route::delete('/{id}', [AdminController::class, 'destroy'])->middleware('admin.permission:admins.destroy');
        Route::put('/{id}/restore', [AdminController::class, 'restore'])->middleware('admin.permission:admins.restore');
    });

    Route::prefix('roles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\RoleController::class, 'index'])->middleware('admin.permission:roles.index');
        // new endpoint to fetch all permissions for role creation/edit
        Route::get('/permissions/all', [\App\Http\Controllers\Api\RoleController::class, 'allPermissions']);
        Route::get('/select', [\App\Http\Controllers\Api\RoleController::class, 'selectAllRoles']);
        Route::post('/', [\App\Http\Controllers\Api\RoleController::class, 'store'])->middleware('admin.permission:roles.store');
        Route::get('/{id}', [\App\Http\Controllers\Api\RoleController::class, 'show'])->middleware('admin.permission:roles.show');
        Route::put('/{id}', [\App\Http\Controllers\Api\RoleController::class, 'update'])->middleware('admin.permission:roles.update');
        Route::delete('/{id}', [\App\Http\Controllers\Api\RoleController::class, 'destroy'])->middleware('admin.permission:roles.destroy');
        Route::put('/{id}/restore', [\App\Http\Controllers\Api\RoleController::class, 'restore'])->middleware('admin.permission:roles.restore');
    });
    // Admin-specific permission assignment endpoints
    Route::get('/admins/{id}/permissions', [\App\Http\Controllers\Api\AdminPermissionController::class, 'adminPermissions']);
    Route::prefix('trip_types')->group(function () {
        Route::get('/', [TripTypeController::class, 'index'])->middleware('admin.permission:trip_types.index');
        Route::get('/export', [TripTypeController::class, 'export'])->middleware('admin.permission:trip_types.export');
        Route::post('/', [TripTypeController::class, 'store'])->middleware('admin.permission:trip_types.store');
        Route::put('/{id}', [TripTypeController::class, 'update'])->middleware(['admin.permission:trip_types.update']);
        Route::get('/{id}', [TripTypeController::class, 'show'])->middleware('admin.permission:trip_types.show');
        Route::put('/{id}/activate', [TripTypeController::class, 'activate'])->middleware('admin.permission:trip_types.activate');
        Route::put('/{id}/suspend', [TripTypeController::class, 'suspend'])->middleware('admin.permission:trip_types.suspend');
        Route::put('/{id}/status-toggle', [TripTypeController::class, 'statusToggle'])->middleware('admin.permission:trip_types.status_toggle');
        Route::put('/{id}/licence-toggle', [TripTypeController::class, 'licenceToggle'])->middleware('admin.permission:trip_types.licence_toggle');
        Route::delete('/{id}', [TripTypeController::class, 'destroy'])->middleware('admin.permission:trip_types.destroy');
        Route::put('/{id}/restore', [TripTypeController::class, 'restore'])->middleware('admin.permission:trip_types.restore');
    });
    Route::prefix('wallets')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->middleware('admin.permission:wallets.index');
        Route::get('/{id}', [WalletController::class, 'show'])->middleware('admin.permission:wallets.show');
    });
    Route::prefix('documents')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\DriverDocumentController::class, 'index'])->middleware('admin.permission:documents.index');
        Route::post('/{id}/accept', [\App\Http\Controllers\Api\DriverDocumentController::class, 'accept'])->middleware('admin.permission:documents.accept');
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\DriverDocumentController::class, 'reject'])->middleware('admin.permission:documents.reject');
    });
    Route::prefix('vehicles')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\VehicleApprovalController::class, 'index'])->middleware('admin.permission:documents.index');
        Route::post('/{id}/accept', [\App\Http\Controllers\Api\VehicleApprovalController::class, 'accept'])->middleware('admin.permission:documents.accept');
        Route::post('/{id}/reject', [\App\Http\Controllers\Api\VehicleApprovalController::class, 'reject'])->middleware('admin.permission:documents.reject');
    });
    Route::get('/offers', [\App\Http\Controllers\Api\OfferController::class, 'index'])->middleware('admin.permission:offers.index');
    Route::post('/offers', [\App\Http\Controllers\Api\OfferController::class, 'store'])->middleware('admin.permission:offers.store');
    Route::get('/offers/{offer}', [\App\Http\Controllers\Api\OfferController::class, 'show'])->middleware('admin.permission:offers.show');
    Route::put('/offers/{offer}', [\App\Http\Controllers\Api\OfferController::class, 'update'])->middleware('admin.permission:offers.update');
    Route::delete('/offers/{offer}', [\App\Http\Controllers\Api\OfferController::class, 'destroy'])->middleware('admin.permission:offers.destroy');

    Route::get('/coupons', [\App\Http\Controllers\Api\CouponController::class, 'index'])->middleware('admin.permission:coupons.index');
    Route::post('/coupons', [\App\Http\Controllers\Api\CouponController::class, 'store'])->middleware('admin.permission:coupons.store');
    Route::get('/coupons/{coupon}', [\App\Http\Controllers\Api\CouponController::class, 'show'])->middleware('admin.permission:coupons.show');
    Route::put('/coupons/{coupon}', [\App\Http\Controllers\Api\CouponController::class, 'update'])->middleware('admin.permission:coupons.update');
    Route::delete('/coupons/{coupon}', [\App\Http\Controllers\Api\CouponController::class, 'destroy'])->middleware('admin.permission:coupons.destroy');
    Route::get('/trips', [\App\Http\Controllers\Api\AdminTripController::class, 'index'])->middleware('admin.permission:trips.index');
});

// Fallback to serve storage files via API if public/storage symlink is not available.
// Usage: GET /storage/{any/path/to/file}
// NOTE: Preferred solution is to run `php artisan storage:link` so webserver serves /storage/* directly.
