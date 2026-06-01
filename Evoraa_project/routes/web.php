<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Admin Auth Redirects and Login Handlers
Route::post('/admin/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Admin Authentication Shield
// Only authorized administrators can manage inventory and order lists
Route::middleware(['web', 'auth:admin'])->group(function () {
    Route::post('/admin/logout', [AuthController::class, 'logoutAdmin']);

    // Admin Dashboard Actions
    Route::get('/admin/dashboard-state', [AdminDashboardController::class, 'index']);
    Route::post('/admin/update-order-status', [AdminDashboardController::class, 'updateOrderStatus']);
    
    // Catalog updates with secure uploads
    Route::post('/admin/add-product', [AdminDashboardController::class, 'addProduct']);
    Route::post('/admin/edit-product', [AdminDashboardController::class, 'editProduct']);
    Route::post('/admin/delete-product', [AdminDashboardController::class, 'deleteProduct']);
    
    // Announcements updates
    Route::post('/admin/add-announcement', [AdminDashboardController::class, 'addAnnouncement']);
    Route::post('/admin/delete-announcement', [AdminDashboardController::class, 'deleteAnnouncement']);
    
    // Marketing slide updates
    Route::post('/admin/add-banner', [AdminDashboardController::class, 'addBanner']);
    Route::post('/admin/delete-banner', [AdminDashboardController::class, 'deleteBanner']);
    
    // Profile settings
    Route::post('/admin/create-admin', [AdminDashboardController::class, 'createAdmin']);
    Route::post('/admin/delete-admin', [AdminDashboardController::class, 'deleteAdmin']);
});
