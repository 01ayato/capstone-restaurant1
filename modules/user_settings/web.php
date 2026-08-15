<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/user_settings', function () {
        return view('modules.user_settings.index');
    })->name('module.user_settings.index');
});
