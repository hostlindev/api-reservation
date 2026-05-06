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

// Settings & Locals
Route::prefix('v1')->group(function () {
    Route::prefix('public')->group(function () {
        // List all locals
        Route::get('/locals', [\App\Http\Controllers\Api\V1\Public\LocalController::class, 'index']);

        // Show specific local and its active courts
        Route::get('/locals/{local:slug}', [\App\Http\Controllers\Api\V1\Public\LocalController::class, 'show']);

        // List all courts or show a specific court details globally (Publicly)
        Route::get('/courts', [\App\Http\Controllers\Api\V1\Public\CourtController::class, 'index']);
        Route::get('/courts/{court}', [\App\Http\Controllers\Api\V1\Public\CourtController::class, 'show']);
        
        // Availability Endpoints for Vue (Calendar and Hours)
        Route::get('/courts/{court}/available-dates', [\App\Http\Controllers\Api\V1\Public\CourtController::class, 'getAvailableDates']);
        Route::get('/courts/{court}/available-blocks', [\App\Http\Controllers\Api\V1\Public\CourtController::class, 'getAvailableBlocks']);

        // General Unified Availability Endpoint
        Route::get('/availability', [\App\Http\Controllers\Api\V1\Public\AvailabilityController::class, 'index']);

        // Find availability for a given Local based on category
        Route::get('/locals/{local:slug}/availability', [\App\Http\Controllers\Api\V1\Public\BookingController::class, 'getAvailability']);

        // Lock a court
        Route::post('/locals/{local:slug}/bookings/lock', [\App\Http\Controllers\Api\V1\Public\BookingController::class, 'createLock']);

        // Confirm booking
        Route::post('/locals/{local:slug}/bookings/confirm', [\App\Http\Controllers\Api\V1\Public\BookingController::class, 'confirm']);
    });
});

// Admin Routes (Protected)
Route::prefix('v1/admin')->group(function () {

    // Auth Login
    Route::post('/login', [\App\Http\Controllers\Api\V1\Admin\AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {

        // Super Admin only routes (all-access)
        Route::middleware('ability:all-access')->group(function () {
            Route::apiResource('locals', \App\Http\Controllers\Api\V1\Admin\LocalController::class);
        });

        // Local Admin or Super Admin routes (requires at least local-access or all-access)
        Route::middleware('ability:all-access,local-access')->group(function () {
            // Dashboard & Finances
            Route::get('dashboard/finances', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'finances']);

            // Courts CRUD
            Route::apiResource('courts', \App\Http\Controllers\Api\V1\Admin\CourtController::class);

            // Staff Management (Local Admin only usually, but allowed for super admin too)
            Route::apiResource('users', \App\Http\Controllers\Api\V1\Admin\UserController::class)->except(['update', 'show']);

            // Bookings Management
            Route::get('bookings', [\App\Http\Controllers\Api\V1\Admin\BookingController::class, 'index']);
            Route::get('bookings/{booking}', [\App\Http\Controllers\Api\V1\Admin\BookingController::class, 'show']);
            Route::patch('bookings/{booking}/status', [\App\Http\Controllers\Api\V1\Admin\BookingController::class, 'updateStatus']);
            Route::post('bookings/{booking}/cancel', [\App\Http\Controllers\Api\V1\Admin\BookingController::class, 'updateStatus']); // Alias for cancellation
        });
    });
});
