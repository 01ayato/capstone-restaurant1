<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/authorization', function () {
        return view('modules.authorization.index');
    })->name('module.authorization.index');
});
