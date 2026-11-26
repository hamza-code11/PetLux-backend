<?php

// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Mail\PasswordResetOtpMail;
use App\Http\Controllers\PetTypeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\ContactMessageController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\OrderController;



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
    Route::post('/orders', [OrderController::class, 'store']);
});





// Public Route (login zaroori nahi)
Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe']);
Route::post('contact', [ContactMessageController::class, 'store']);
Route::get('shop/products', [ProductController::class, 'publicIndex']);
Route::get('shop/filters', [ProductController::class, 'getFilters']); // Naya route
Route::get('menu', [MenuController::class, 'index']);
Route::get('shop/products/{slug}', [ProductController::class, 'publicShow']);
Route::get('pet-types', [PetTypeController::class, 'publicIndex']);
Route::get('/products/{slug}/reviews', [ReviewController::class, 'index']);





Route::middleware('auth:sanctum')->group(function () {
    Route::post('/products/{slug}/reviews', [ReviewController::class, 'store']);

});





Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    
    // Pet Types Routes
    Route::get('pet-types', [PetTypeController::class, 'index']);
    Route::post('pet-types', [PetTypeController::class, 'store']);
    Route::get('pet-types/{petType}', [PetTypeController::class, 'show']);
    Route::put('pet-types/{petType}', [PetTypeController::class, 'update']);
    Route::delete('pet-types/{petType}', [PetTypeController::class, 'destroy']);
    Route::patch('pet-types/{petType}/toggle-navbar', [PetTypeController::class, 'toggleNavbar']);


    // Category Routes
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::put('categories/{category}', [CategoryController::class, 'update']);
    Route::delete('categories/{category}', [CategoryController::class, 'destroy']);


    // Subcategory Routes
    Route::get('subcategories', [SubcategoryController::class, 'index']);
    Route::post('subcategories', [SubcategoryController::class, 'store']);
    Route::get('subcategories/{subcategory}', [SubcategoryController::class, 'show']);
    Route::put('subcategories/{subcategory}', [SubcategoryController::class, 'update']);
    Route::delete('subcategories/{subcategory}', [SubcategoryController::class, 'destroy']);

    // Products Routes
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::put('products/{product}', [ProductController::class, 'update']);
    Route::delete('products/{product}', [ProductController::class, 'destroy']);
    

    // Newsletter Routes
    Route::get('newsletters', [NewsletterController::class, 'index']);
    Route::delete('newsletters/{newsletter}', [NewsletterController::class, 'destroy']);


    // Contact Messages Routes
    Route::get('contact-messages', [ContactMessageController::class, 'index']);
    Route::get('contact-messages/{contactMessage}', [ContactMessageController::class, 'show']);
    Route::patch('contact-messages/{contactMessage}/status', [ContactMessageController::class, 'updateStatus']);
    Route::delete('contact-messages/{contactMessage}', [ContactMessageController::class, 'destroy']);


    // Reviews Settings
    Route::get('/reviews', [ReviewController::class, 'adminIndex']);
    Route::patch('/reviews/{id}/approve', [ReviewController::class, 'approve']);
    Route::patch('/reviews/{id}/reject', [ReviewController::class, 'reject']);
    Route::delete('/reviews/{id}', [ReviewController::class, 'destroy']);


    // Shipping Settings
    Route::get('/settings/shipping', [SettingController::class, 'getShippingCharges']);
    Route::post('/settings/shipping', [SettingController::class, 'updateShippingCharges']);


    // Payment Settings Routes
    Route::get('/settings/payment', [SettingController::class, 'getPaymentSettings']);
    Route::post('/settings/payment', [SettingController::class, 'updatePaymentSettings']);




    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::put('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);

    


});

