<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\MenuItemController;
use App\Http\Controllers\Admin\OrderManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReservationController;
use App\Http\Controllers\Admin\RestaurantSettingController;
use App\Http\Controllers\Portal\CartController;
use App\Http\Controllers\Portal\CheckoutController;
use App\Http\Controllers\Portal\FeedbackController as PortalFeedbackController;
use App\Http\Controllers\Portal\LandingController;
use App\Http\Controllers\Portal\MenuController;
use App\Http\Controllers\Portal\OrderController;
use App\Http\Controllers\Portal\ProfileController;
use App\Http\Controllers\Portal\ReservationController as PortalReservationController;
use App\Http\Controllers\Portal\ReviewsController;
use App\Http\Controllers\Portal\SitController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;
    

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/reviews', [ReviewsController::class, 'index'])->name('reviews.index');
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index');

Route::get('/sit/lookup', [SitController::class, 'lookup'])->name('sit.lookup');
Route::post('/sit/select', [SitController::class, 'selectManual'])->name('sit.select');
Route::post('/sit/clear', [SitController::class, 'clear'])->name('sit.clear');

Route::get('/sit/{sitCode}', [SitController::class, 'show'])->name('sit.show');
Route::post('/sit/{sitCode}/occupy', [SitController::class, 'occupy'])->name('sit.occupy');
Route::post('/sit/{sitCode}/release', [SitController::class, 'release'])->name('sit.release');

Route::prefix('cart')->name('cart.')->group(function (): void {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::post('/update', [CartController::class, 'update'])->name('update');
    Route::post('/remove', [CartController::class, 'remove'])->name('remove');
});

Route::middleware(['auth', 'verified:auth.verify-email'])->group(function (): void {
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
    Route::post('/checkout/scan-payment-proof', [CheckoutController::class, 'scanPaymentProof'])->name('checkout.scan-payment-proof');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/my-orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/reservations', [PortalReservationController::class, 'index'])->name('reservations.index');
    Route::get('/my-reservations', [PortalReservationController::class, 'mine'])->name('reservations.mine');
    Route::get('/reservations/availability', [PortalReservationController::class, 'availability'])->name('reservations.availability');
    Route::post('/reservations/scan-payment-proof', [PortalReservationController::class, 'scanPaymentProof'])->name('reservations.scan-payment-proof');
    Route::post('/reservations', [PortalReservationController::class, 'store'])->name('reservations.store');
    Route::patch('/reservations/{reservation}/cancel', [PortalReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::get('/feedback', [PortalFeedbackController::class, 'create'])->name('feedback.create');
    Route::post('/feedback', [PortalFeedbackController::class, 'store'])->name('feedback.store');
});

Route::middleware(['auth', 'verified:auth.verify-email', EnsureStaff::class])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/menu-items', [MenuItemController::class, 'index'])->name('menu-items.index');
        Route::post('/menu-items', [MenuItemController::class, 'store'])->name('menu-items.store');
        Route::put('/menu-items/{menuItem}', [MenuItemController::class, 'update'])->name('menu-items.update');
        Route::patch('/menu-items/{menuItem}/signature', [MenuItemController::class, 'toggleSignature'])->name('menu-items.signature');
        Route::delete('/menu-items/{menuItem}', [MenuItemController::class, 'destroy'])->name('menu-items.destroy');

        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('/orders', [OrderManagementController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderManagementController::class, 'show'])->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderManagementController::class, 'updateStatus'])->name('orders.status');
        Route::delete('/orders/{order}', [OrderManagementController::class, 'destroy'])->name('orders.destroy');
        Route::get('/orders/{order}/receipt', [OrderManagementController::class, 'receipt'])->name('orders.receipt');

        Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');

        Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
        Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
        Route::put('/reservations/{reservation}', [ReservationController::class, 'update'])->name('reservations.update');
        Route::patch('/reservations/{reservation}/status', [ReservationController::class, 'updateStatus'])->name('reservations.status');
        Route::post('/reservations/{reservation}/notify', [ReservationController::class, 'notify'])->name('reservations.notify');
        Route::get('/reservations/{reservation}/receipt', [ReservationController::class, 'receipt'])->name('reservations.receipt');
        Route::delete('/reservations/{reservation}', [ReservationController::class, 'destroy'])->name('reservations.destroy');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');

        Route::get('/settings', [RestaurantSettingController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [RestaurantSettingController::class, 'update'])->name('settings.update');

        Route::get('/feedback', [FeedbackController::class, 'index'])->name('feedback.index');
        Route::patch('/feedback/{feedback}/status', [FeedbackController::class, 'updateStatus'])->name('feedback.status');
        Route::post('/feedback/{feedback}/contact', [FeedbackController::class, 'contact'])->name('feedback.contact');
        Route::delete('/feedback/{feedback}', [FeedbackController::class, 'destroy'])->name('feedback.destroy');
    });
});
