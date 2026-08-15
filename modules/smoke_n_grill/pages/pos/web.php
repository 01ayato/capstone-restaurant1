<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\PosController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureStaff::class])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/pos', [PosController::class, 'index'])
        ->name('page.smoke_n_grill.pos.index');
    Route::post('/smoke_n_grill/pos', [PosController::class, 'store'])
        ->name('page.smoke_n_grill.pos.store');
});
