<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Public Storefront Catalog
Route::get('/shop-data', [StorefrontController::class, 'getShopData']);
Route::get('/testimonials', [StorefrontController::class, 'getTestimonials']);
Route::post('/contact', [StorefrontController::class, 'submitContact']);
Route::post('/feedback', [StorefrontController::class, 'submitFeedback']);

// Checkout and Order Tracking
Route::post('/checkout', [CheckoutController::class, 'checkout']);
Route::get('/track-order', [CheckoutController::class, 'trackOrder']);

// Rate Limited Endpoints (Vulnerability #4 Fix)
Route::middleware('throttle:login')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('throttle:uploads')->group(function () {
    Route::post('/register', [StorefrontController::class, 'registerCustomer']);
    Route::post('/upload-receipt', [CheckoutController::class, 'uploadReceipt']);
});

// Authenticated Customer Dashboard (Vulnerability #3 Fix)
// Fully locked down with Sanctum Token checks.
Route::middleware('auth:sanctum')->group(function () {
    Route::match(['get', 'post'], '/dashboard', [CustomerDashboardController::class, 'index']);
});
