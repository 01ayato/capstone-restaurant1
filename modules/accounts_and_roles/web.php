<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/accounts_and_roles', function () {
        return view('modules.accounts_and_roles.index');
    })->name('module.accounts_and_roles.index');
});
