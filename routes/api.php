<?php
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public Auth Routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // Protected Auth Routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    });

    // Events - public read
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{id}', [EventController::class, 'show']);

    // Events - organizer/admin write
    Route::middleware(['auth:sanctum', 'role:admin,organizer'])->group(function () {
        Route::post('/events', [EventController::class, 'store']);
        Route::put('/events/{id}', [EventController::class, 'update']);
        Route::delete('/events/{id}', [EventController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'role:admin,organizer'])->group(function () {
        Route::post('/events/{event_id}/tickets', [TicketController::class, 'store']);
        Route::put('/tickets/{id}', [TicketController::class, 'update']);
        Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);
    });

    // Bookings - customer
    Route::middleware(['auth:sanctum', 'role:customer'])->group(function () {
        Route::post('/tickets/{ticket}/bookings', [BookingController::class, 'store'])
            ->middleware('prevent.double.booking');
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::put('/bookings/{id}/cancel', [BookingController::class, 'cancel']);
    });

    // Bookings - admin management
    Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::get('/admin/bookings', [BookingController::class, 'adminIndex']);
        Route::put('/admin/bookings/{id}/cancel', [BookingController::class, 'adminCancel']);
    });

    // Payments
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/bookings/{bookingId}/payment', [PaymentController::class, 'store']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
    });
});