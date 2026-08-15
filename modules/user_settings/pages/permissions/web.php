<?php

declare(strict_types=1);

use App\Http\Controllers\Modules\UserSettings\PermissionsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->prefix('modules')->group(function (): void {
    Route::get('/user_settings/permissions', [PermissionsController::class, 'index'])
        ->name('page.user_settings.permissions.index');
    Route::post('/user_settings/permissions', [PermissionsController::class, 'update'])
        ->name('page.user_settings.permissions.update');
});
