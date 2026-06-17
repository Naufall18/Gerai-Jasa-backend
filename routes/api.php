<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\VendorBookingController;
use App\Http\Controllers\Api\V1\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Guest Auth routes
    Route::prefix('auth')->group(function () {
        Route::post('/request-otp', [AuthController::class, 'requestOtp']);
        Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);

        // Protected Auth routes
        Route::middleware('auth:sanctum')->group(function () {
            Route::get('/me', [AuthController::class, 'me']);
            Route::post('/logout', [AuthController::class, 'logout']);
        });
    });

    // Public vendor browsing routes
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::get('/vendors/{slug}', [VendorController::class, 'show']);
    Route::get('/vendors/{vendorId}/slots', [VendorController::class, 'getSlots']);

    // Protected customer booking routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index']);
            Route::post('/', [BookingController::class, 'store']);
            Route::get('/{id}', [BookingController::class, 'show']);
            Route::patch('/{id}/cancel', [BookingController::class, 'cancel']);
        });

        // Protected vendor booking routes
        Route::prefix('vendor/bookings')->group(function () {
            Route::get('/', [VendorBookingController::class, 'index']);
            Route::patch('/{id}/confirm', [VendorBookingController::class, 'confirm']);
            Route::patch('/{id}/complete', [VendorBookingController::class, 'complete']);
        });
    });

    // Payment webhook routes (public, exempt from auth + CSRF)
    Route::withoutMiddleware(['auth:sanctum'])->group(function () {
        Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans']);
        Route::post('/webhooks/xendit', [WebhookController::class, 'xendit']);
    });
});
