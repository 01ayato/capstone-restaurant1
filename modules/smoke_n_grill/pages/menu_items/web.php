<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\MenuItemsController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureStaff::class])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/menu_items', [MenuItemsController::class, 'index'])
        ->name('page.smoke_n_grill.menu_items.index');
    Route::post('/smoke_n_grill/menu_items', [MenuItemsController::class, 'store'])
        ->name('page.smoke_n_grill.menu_items.store');
    Route::patch('/smoke_n_grill/menu_items/{menu_item}', [MenuItemsController::class, 'update'])
        ->name('page.smoke_n_grill.menu_items.update');
    Route::delete('/smoke_n_grill/menu_items/{menu_item}', [MenuItemsController::class, 'destroy'])
        ->name('page.smoke_n_grill.menu_items.destroy');
});
