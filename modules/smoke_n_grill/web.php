<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureStaff;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', EnsureStaff::class])->prefix('modules')->group(function (): void {
    Route::get('/smoke_n_grill', function () {
        return view('modules.smoke_n_grill.index');
    })->name('module.smoke_n_grill.index');
});
