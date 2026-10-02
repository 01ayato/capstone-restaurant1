<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\OrdersController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/orders', [OrdersController::class, 'index'])
        ->name('page.smoke_n_grill.orders.index');
    Route::get('/smoke_n_grill/orders/{order}', [OrdersController::class, 'show'])
        ->name('page.smoke_n_grill.orders.show');
    Route::get('/smoke_n_grill/orders/{order}/receipt', [OrdersController::class, 'receipt'])
        ->name('page.smoke_n_grill.orders.receipt');
    Route::patch('/smoke_n_grill/orders/{order}/status', [OrdersController::class, 'updateStatus'])
        ->name('page.smoke_n_grill.orders.status');
    Route::delete('/smoke_n_grill/orders/{order}', [OrdersController::class, 'destroy'])
        ->name('page.smoke_n_grill.orders.destroy');
});
