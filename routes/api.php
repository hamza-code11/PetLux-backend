<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Mail\PasswordResetOtpMail;
use App\Http\Controllers\PetTypeController;




Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateProfile']);
    Route::post('/verify-password', [AuthController::class, 'verifyPassword']);
    Route::put('/change-password', [AuthController::class, 'updatePassword']);
    Route::post('/logout', [AuthController::class, 'logout']);
});





Route::middleware('auth:sanctum')->group(function () {

});





Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    
    Route::get('pet-types', [PetTypeController::class, 'index']);        // List all
    Route::post('pet-types', [PetTypeController::class, 'store']);       // Create new
    Route::get('pet-types/{petType}', [PetTypeController::class, 'show']); // Get single
    Route::put('pet-types/{petType}', [PetTypeController::class, 'update']); // Update
    Route::delete('pet-types/{petType}', [PetTypeController::class, 'destroy']); // Delete
});

