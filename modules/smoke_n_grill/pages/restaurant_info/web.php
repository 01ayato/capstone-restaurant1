<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\RestaurantInfoController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureStaff::class])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/restaurant_info', [RestaurantInfoController::class, 'index'])
        ->name('page.smoke_n_grill.restaurant_info.index');
    Route::post('/smoke_n_grill/restaurant_info', [RestaurantInfoController::class, 'update'])
        ->name('page.smoke_n_grill.restaurant_info.update');
});
