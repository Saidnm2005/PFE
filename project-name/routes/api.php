<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RouteController;
use App\Http\Controllers\StationController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
Route::middleware('auth:sanctum')->group(function () {

    Route::apiResource('stations', StationController::class);
    Route::get('routes', [RouteController::class, 'index']);
    Route::get('routes/{user}/generate', [RouteController::class, 'generate']);
    Route::get('routes/{route}', [RouteController::class, 'show']);
    Route::get('users', [UserController::class, 'index'])->middleware('admin');
    Route::get('users/{user}', [UserController::class, 'show']);
    Route::patch('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    
});

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::post('/register', [RegisteredUserController::class, 'store']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Token Required)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);

    Route::post('/email/verification-notification', 
        [EmailVerificationNotificationController::class, 'store']
    )->middleware('throttle:6,1');

    Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1']);
});


