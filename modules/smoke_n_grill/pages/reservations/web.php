<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\ReservationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/reservations', [ReservationsController::class, 'index'])
        ->name('page.smoke_n_grill.reservations.index');
    Route::post('/smoke_n_grill/reservations', [ReservationsController::class, 'store'])
        ->name('page.smoke_n_grill.reservations.store');
    Route::patch('/smoke_n_grill/reservations/{reservation}', [ReservationsController::class, 'update'])
        ->name('page.smoke_n_grill.reservations.update');
    Route::delete('/smoke_n_grill/reservations/{reservation}', [ReservationsController::class, 'destroy'])
        ->name('page.smoke_n_grill.reservations.destroy');
});
