<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\SmokeNGrill\CustomersController;
use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureStaff::class])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill/customers', [CustomersController::class, 'index'])
        ->name('page.smoke_n_grill.customers.index');
});
