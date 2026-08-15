<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\ReservationsController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureStaff::class])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/reservations', [ReservationsController::class, 'index'])
        ->name('page.smoke_n_grill.reservations.index');
    Route::post('/smoke_n_grill/reservations', [ReservationsController::class, 'store'])
        ->name('page.smoke_n_grill.reservations.store');
    Route::get('/smoke_n_grill/reservations/{reservation}/receipt', [ReservationsController::class, 'receipt'])
        ->name('page.smoke_n_grill.reservations.receipt');
    Route::delete('/smoke_n_grill/reservations/{reservation}', [ReservationsController::class, 'destroy'])
        ->name('page.smoke_n_grill.reservations.destroy');
});
