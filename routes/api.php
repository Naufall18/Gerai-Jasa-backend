<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\ReviewController;
use App\Http\Controllers\Api\V1\VendorBookingController;
use App\Http\Controllers\Api\V1\VendorController;
use App\Http\Controllers\Api\V1\VendorProfileController;
use App\Http\Controllers\Api\V1\VendorServiceController;
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
            Route::post('/fcm-token', [AuthController::class, 'updateFcmToken']);
        });
    });

    // Public category & vendor browsing routes
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::get('/vendors', [VendorController::class, 'index']);
    Route::get('/vendors/{slug}', [VendorController::class, 'show']);
    Route::get('/vendors/{vendorId}/slots', [VendorController::class, 'getSlots']);
    Route::get('/vendors/{vendorId}/reviews', [ReviewController::class, 'vendorReviews']);

    // Protected customer routes
    Route::middleware('auth:sanctum')->group(function () {
        // Bookings
        Route::prefix('bookings')->group(function () {
            Route::get('/', [BookingController::class, 'index']);
            Route::post('/', [BookingController::class, 'store']);
            Route::get('/{id}', [BookingController::class, 'show']);
            Route::patch('/{id}/cancel', [BookingController::class, 'cancel']);
            Route::post('/{id}/review', [ReviewController::class, 'store']);
        });

        // Vendor management routes (role: vendor)
        Route::prefix('vendor')->group(function () {
            Route::prefix('bookings')->group(function () {
                Route::get('/', [VendorBookingController::class, 'index']);
                Route::patch('/{id}/confirm', [VendorBookingController::class, 'confirm']);
                Route::patch('/{id}/complete', [VendorBookingController::class, 'complete']);
            });
            Route::patch('/reviews/{id}/reply', [ReviewController::class, 'vendorReply']);

            // Vendor profile & schedule management
            Route::get('/profile',          [VendorProfileController::class, 'show']);
            Route::patch('/profile',        [VendorProfileController::class, 'update']);
            Route::patch('/schedules',      [VendorProfileController::class, 'updateSchedules']);

            // Vendor services CRUD
            Route::get('/services',         [VendorServiceController::class, 'index']);
            Route::post('/services',        [VendorServiceController::class, 'store']);
            Route::patch('/services/{id}',  [VendorServiceController::class, 'update']);
            Route::delete('/services/{id}', [VendorServiceController::class, 'destroy']);
        });
    });

    // Payment webhook routes (public, exempt from auth + CSRF)
    Route::withoutMiddleware(['auth:sanctum'])->group(function () {
        Route::post('/webhooks/midtrans', [WebhookController::class, 'midtrans']);
        Route::post('/webhooks/xendit', [WebhookController::class, 'xendit']);
    });
});
