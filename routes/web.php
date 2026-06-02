<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'landing'])->name('home');

Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
Route::get('/marketplace/{marketplaceItem}', [MarketplaceController::class, 'show'])->name('marketplace.show');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
    Route::get('/signup', [AuthController::class, 'showRegister'])->name('signup');
    Route::post('/signup', [AuthController::class, 'register'])->middleware('throttle:6,1')->name('signup.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'email'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:5,1')->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/email/verify', [VerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [VerificationController::class, 'send'])
        ->middleware('throttle:3,1')
        ->name('verification.send');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('throttle:12,1')->name('profile.update');
    Route::patch('/profile/password', [ProfileController::class, 'password'])->middleware('throttle:5,1')->name('profile.password');
    Route::post('/profile/avatar', [ProfileController::class, 'avatar'])->middleware('throttle:6,1')->name('profile.avatar');
    Route::post('/profile/identity', [ProfileController::class, 'identity'])->middleware('throttle:3,1')->name('profile.identity');
    Route::post('/profile/phone/otp', [ProfileController::class, 'sendPhoneOtp'])->middleware('throttle:3,1')->name('profile.phone.otp');
    Route::post('/profile/phone/verify', [ProfileController::class, 'verifyPhoneOtp'])->middleware('throttle:6,1')->name('profile.phone.verify');
    Route::get('/listings/create', [MarketplaceController::class, 'create'])->name('marketplace.create');
    Route::post('/listings', [MarketplaceController::class, 'store'])->name('marketplace.store');
    Route::post('/marketplace/{marketplaceItem}/inquire', [MarketplaceController::class, 'inquire'])->name('marketplace.inquire');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('admin')->group(function (): void {
        Route::get('/admin', [AdminController::class, 'dashboard'])->name('admin.dashboard');
        Route::get('/admin/export/{type}', [AdminController::class, 'export'])->name('admin.export');
        Route::get('/admin/audit', [AdminController::class, 'audit'])->name('admin.audit.index');
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users.index');
        Route::get('/admin/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
        Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
        Route::post('/admin/users/bulk', [AdminController::class, 'bulkUsers'])->name('admin.users.bulk');
        Route::get('/admin/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
        Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
        Route::patch('/admin/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('admin.users.role');
        Route::delete('/admin/users/{user}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');

        Route::get('/admin/posts', [AdminController::class, 'posts'])->name('admin.posts.index');
        Route::get('/admin/posts/create', [AdminController::class, 'createPost'])->name('admin.posts.create');
        Route::post('/admin/posts', [AdminController::class, 'storePost'])->name('admin.posts.store');
        Route::get('/admin/posts/{marketplaceItem}/edit', [AdminController::class, 'editPost'])->name('admin.posts.edit');
        Route::patch('/admin/posts/{marketplaceItem}', [AdminController::class, 'updatePost'])->name('admin.posts.update');
        Route::patch('/admin/posts/{marketplaceItem}/moderate', [AdminController::class, 'moderatePost'])->name('admin.posts.moderate');
        Route::delete('/admin/posts/{marketplaceItem}', [AdminController::class, 'destroyPost'])->name('admin.posts.destroy');

        Route::get('/admin/orders', [AdminController::class, 'orders'])->name('admin.orders.index');
        Route::get('/admin/orders/create', [AdminController::class, 'createOrder'])->name('admin.orders.create');
        Route::post('/admin/orders', [AdminController::class, 'storeOrder'])->name('admin.orders.store');
        Route::post('/admin/orders/bulk', [AdminController::class, 'bulkOrders'])->name('admin.orders.bulk');
        Route::get('/admin/orders/{inquiry}/edit', [AdminController::class, 'editOrder'])->name('admin.orders.edit');
        Route::patch('/admin/orders/{inquiry}', [AdminController::class, 'updateOrder'])->name('admin.orders.update');
        Route::patch('/admin/orders/{inquiry}/status', [AdminController::class, 'updateOrderStatus'])->name('admin.orders.status');
        Route::delete('/admin/orders/{inquiry}', [AdminController::class, 'destroyOrder'])->name('admin.orders.destroy');

        Route::get('/admin/settings', [AdminController::class, 'editSettings'])->name('admin.settings.edit');
        Route::patch('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');
    });
});
